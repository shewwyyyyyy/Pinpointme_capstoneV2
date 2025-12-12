<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Room;
use Symfony\Component\HttpFoundation\Response;

class OpenAIController extends Controller
{
    private string $apiBase = 'https://api.openai.com/v1';

    private function apiKey(): string
    {
        $key = config('services.openai.key') ?: env('OPENAI_API_KEY');
        if (!$key) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Missing OpenAI API key');
        }
        return $key;
    }

    private function httpJson(string $url, array $payload, int $timeout = 60)
    {
        $resp = Http::withToken($this->apiKey())
            ->timeout($timeout)
            ->acceptJson()
            ->asJson()
            ->post($url, $payload);
        if ($resp->failed()) {
            Log::warning('OpenAI API error', ['url' => $url, 'status' => $resp->status(), 'body' => $resp->body()]);
            abort($resp->status(), $resp->body());
        }
        return $resp->json();
    }

    private function uploadAudioForTranscription(string $model, string $path, ?string $responseFormat = null): array
    {
        $fileContents = Storage::get($path);
        $filename = basename($path);

        $resp = Http::withToken($this->apiKey())
            ->timeout(120)
            ->attach('file', $fileContents, $filename)
            ->post($this->apiBase . '/audio/transcriptions', [
                'model' => $model,
                'response_format' => $responseFormat,
            ]);

        if ($resp->failed()) {
            Log::warning('OpenAI transcription error', ['status' => $resp->status(), 'body' => $resp->body()]);
            abort($resp->status(), $resp->body());
        }
        return $resp->json();
    }

    // POST /openai/transcribe (multipart: file|audio) or JSON { audio_path }
    public function transcribe(Request $request)
    {
        // Check if API key is configured
        $apiKey = config('services.openai.key') ?: env('OPENAI_API_KEY');
        if (!$apiKey) {
            Log::error('OpenAI API key not configured');
            return response()->json([
                'error' => 'api_key_missing',
                'message' => 'OpenAI API key is not configured. Please add OPENAI_API_KEY to your .env file.'
            ], 500);
        }

        // Custom validator to avoid redirect (which causes CORS 302 issues)
        $validator = Validator::make($request->all(), [
            'audio_path' => 'required_without:file|required_without:audio|string',
            'file' => 'required_without:audio_path|file|mimes:mp3,wav,webm,ogg,m4a|max:20480',
            'audio' => 'required_without:audio_path|file|mimes:mp3,wav,webm,ogg,m4a|max:20480',
        ]);
        if ($validator->fails()) {
            Log::warning('Transcription validation failed', ['errors' => $validator->errors()->toArray()]);
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()], 422);
        }

        $path = null;
        // If a file is uploaded, store temporarily
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('temp-audio');
            Log::info('Audio file stored', ['path' => $path, 'original' => $request->file('file')->getClientOriginalName()]);
        } elseif ($request->hasFile('audio')) {
            $path = $request->file('audio')->store('temp-audio');
            Log::info('Audio file stored', ['path' => $path, 'original' => $request->file('audio')->getClientOriginalName()]);
        } else {
            $path = $request->input('audio_path');
        }

        if (!$path || !Storage::exists($path)) {
            Log::error('Audio file not found', ['path' => $path]);
            return response()->json(['error' => 'audio_not_found', 'detail' => $path], 404);
        }

        $errors = [];
        $models = [['whisper-1', null], ['gpt-4o-transcribe', 'json']];
        foreach ($models as [$model, $format]) {
            try {
                Log::info("Attempting transcription with model: $model");
                $json = $this->uploadAudioForTranscription($model, $path, $format);
                $text = $json['text'] ?? $json['transcript'] ?? $json['transcription'] ?? $json['result'] ?? null;
                if (!$text && isset($json['segments']) && is_array($json['segments'])) {
                    $text = collect($json['segments'])->pluck('text')->join(' ');
                }
                if ($text) {
                    // Cleanup temp file if we created it
                    if ($request->hasFile('file') || $request->hasFile('audio')) {
                        Storage::delete($path);
                    }
                    Log::info("Transcription successful with model: $model", ['text_length' => strlen($text)]);
                    return response()->json(['model' => $model, 'transcript' => $text, 'raw' => $json]);
                }
            } catch (\Throwable $e) {
                $errorMsg = $e->getMessage();
                Log::warning('Transcription attempt failed', ['model' => $model, 'error' => $errorMsg]);
                $errors[] = ['model' => $model, 'error' => $errorMsg];
            }
        }
        if ($request->hasFile('file') || $request->hasFile('audio')) {
            Storage::delete($path);
        }
        
        Log::error('All transcription attempts failed', ['errors' => $errors]);
        return response()->json([
            'error' => 'transcription_failed',
            'message' => 'All transcription attempts failed. Check if your OpenAI API key is valid and has credits.',
            'attempts' => $errors
        ], 500);
    }

    // POST /openai/translate { text }
    public function translate(Request $request)
    {
        $request->validate(['text' => 'required|string']);
        $data = $this->httpJson($this->apiBase . '/chat/completions', [
            'model' => 'gpt-4o-mini',
            'temperature' => 0,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a precise translation engine. Detect the language of the user text and output ONLY an accurate English translation. If the text is already English, output it unchanged. No commentary.'],
                ['role' => 'user', 'content' => $request->input('text')]
            ]
        ]);
        $translated = $data['choices'][0]['message']['content'] ?? $request->input('text');
        return response()->json(['translated' => trim($translated), 'raw' => $data]);
    }

    // Build catalog snippet for room list
    private function buildRoomCatalogSnippet(int $limit = 800): string
    {
        $lines = [];
        $buildings = Building::with(['floors.rooms'])->get();
        foreach ($buildings as $b) {
            foreach ($b->floors as $f) {
                foreach ($f->rooms as $r) {
                    $lines[] = implode('|', [
                        $r->id,
                        str_replace(['\n', '|'], ' ', $b->name ?? ''),
                        str_replace(['\n', '|'], ' ', $f->floor_name ?? $f->name ?? ''),
                        str_replace(['\n', '|'], ' ', $r->room_name ?? $r->name ?? ''),
                        $b->id,
                        $f->id,
                    ]);
                    if (count($lines) >= $limit) return implode("\n", $lines);
                }
            }
        }
        return implode("\n", $lines);
    }

    // Utilities for string similarity / inference
    private function tokenize(string $s): array
    {
        return array_values(array_filter(preg_split('/\s+/', strtolower(preg_replace('/[^a-z0-9\s]/', ' ', $s))) ?? []));
    }

    private function jaccard(array $a, array $b): float
    {
        $sa = array_unique($a);
        $sb = array_unique($b);
        if (!$sa || !$sb) return 0;
        $i = count(array_intersect($sa, $sb));
        $u = count($sa) + count($sb) - $i;
        return $u ? $i / $u : 0;
    }

    private function levenshteinScore(string $a, string $b): float
    {
        $a = trim(strtolower($a));
        $b = trim(strtolower($b));
        if ($a === $b) return 1;
        $lev = levenshtein($a, $b);
        $max = max(strlen($a), strlen($b)) ?: 1;
        return 1 - ($lev / $max);
    }

    private function stringSimilarity(string $a, string $b): float
    {
        if (!$a || !$b) return 0;
        $lev = $this->levenshteinScore($a, $b);
        $tok = $this->jaccard($this->tokenize($a), $this->tokenize($b));
        return ($lev * 0.6) + ($tok * 0.4);
    }

    private function inferLocation(string $transcript): ?array
    {
        $lower = strtolower($transcript);
        $buildings = Building::with(['floors.rooms'])->get();
        $flat = [];
        foreach ($buildings as $b) {
            foreach ($b->floors as $f) {
                foreach ($f->rooms as $r) {
                    $flat[] = [
                        'room_id' => $r->id,
                        'room_name' => $r->room_name ?? $r->name ?? '',
                        'floor_id' => $f->id,
                        'floor_name' => $f->floor_name ?? $f->name ?? '',
                        'building_id' => $b->id,
                        'building_name' => $b->name,
                    ];
                }
            }
        }
        $best = null;
        foreach ($flat as $row) {
            $roomNameLower = strtolower($row['room_name']);
            $direct = str_contains($lower, $roomNameLower);
            $fuzzy = $this->stringSimilarity($transcript, $row['room_name']);
            $score = $direct ? max($fuzzy, 0.9) : $fuzzy * 0.75;
            if (!$best || $score > $best['score']) {
                $best = ['score' => $score, 'data' => $row, 'reason' => $direct ? 'direct' : 'fuzzy'];
            }
        }
        if (!$best || $best['score'] < 0.55) return null;
        return [
            'building_id' => $best['data']['building_id'],
            'floor_id' => $best['data']['floor_id'],
            'room_id' => $best['data']['room_id'],
            'building_name_match' => $best['data']['building_name'],
            'floor_name_match' => $best['data']['floor_name'],
            'room_name_match' => $best['data']['room_name'],
            'confidence' => round($best['score'], 3),
            'details' => $best['reason'] . ':' . number_format($best['score'], 2)
        ];
    }

    // POST /openai/extract { transcript }
    public function extract(Request $request)
    {
        $request->validate(['transcript' => 'required|string']);
        $transcript = $request->input('transcript');

        // First translate to English
        $translatedResp = $this->translate(new Request(['text' => $transcript]));
        $translatedJson = $translatedResp->getData(true);
        $english = $translatedJson['translated'] ?? $transcript;

        $catalog = $this->buildRoomCatalogSnippet();

        $systemPrompt = "You are an assistant extracting structured emergency request fields AND inferring the most likely location (room) the caller is in.\nReturn ONLY strict JSON.\nFields: description, mobility_status (mobile|limited|immobile|''), injuries (bleeding|fracture|burn|unconscious|breathing|head|other|''), other_injury (string if injuries=other else ''), urgency_level (low|medium|high|critical), additional_info, room_id, floor_id, building_id, room_name, floor_name, building_name, room_selection_confidence (0-1).\nIf a field is not present, return empty string (or 0 for confidence).\nDESCRIPTION RULES (NO SUMMARIZING): Extract ONLY the exact incident-relevant words/phrases from the English transcript in their original order. Do NOT paraphrase; copy verbatim. Remove non-incident content to additional_info.\nLOCATION SELECTION TASK:\nCatalog lines: roomId|BuildingName|FloorName|RoomName|BuildingId|FloorId\nCatalog (may be truncated):\n$catalog\nConfidence rules: 0.9+ explicit; 0.6-0.89 partial; <0.55 insufficient (then blank IDs).";

        $userPrompt = 'English Transcript: "' . $english . '"';

        $data = $this->httpJson($this->apiBase . '/chat/completions', [
            'model' => 'gpt-4o-mini',
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ]);

        $content = $data['choices'][0]['message']['content'] ?? '{}';
        $parsed = json_decode($content, true) ?: [];
        if (!empty($parsed['room_selection_confidence']) && $parsed['room_selection_confidence'] < 0.55) {
            $parsed['room_id'] = $parsed['floor_id'] = $parsed['building_id'] = '';
            $parsed['room_name'] = $parsed['floor_name'] = $parsed['building_name'] = '';
            $parsed['room_selection_confidence'] = 0;
        }
        return response()->json([
            'fields' => $parsed,
            'translated' => $english,
            'raw_api' => $data,
        ]);
    }

    // POST /openai/extract-full { transcript }
    public function extractFull(Request $request)
    {
        $request->validate(['transcript' => 'required|string']);
        $transcript = $request->input('transcript');
        $extract = $this->extract(new Request(['transcript' => $transcript]));
        $extractData = $extract->getData(true);

        $lower = strtolower($transcript);
        $raw_building_hint = $this->regexFirst($lower, '/\b([a-z0-9\- ]+?) building\b/');
        $raw_room_hint = $this->regexFirst($lower, '/room\s+([a-z0-9\-]+)/');
        $raw_floor_hint = $this->regexFirst($lower, '/(\b\d+(st|nd|rd|th) floor\b|\b(first|second|third|fourth|fifth|sixth|seventh|eighth|ninth|tenth) floor\b)/');

        $fields = $extractData['fields'] ?? [];
        $loc = null;
        if (!empty($fields['room_id']) && ($fields['room_selection_confidence'] ?? 0) >= 0.55) {
            $loc = [
                'building_id' => $fields['building_id'] ?? null,
                'floor_id' => $fields['floor_id'] ?? null,
                'room_id' => $fields['room_id'] ?? null,
                'building_name_match' => $fields['building_name'] ?? null,
                'floor_name_match' => $fields['floor_name'] ?? null,
                'room_name_match' => $fields['room_name'] ?? null,
                'confidence' => $fields['room_selection_confidence'] ?? 0,
                'details' => 'ai-selection'
            ];
        } else {
            $loc = $this->inferLocation($transcript);
        }

        return response()->json([
            'transcript' => $transcript,
            'fields' => $fields,
            'raw_building_hint' => $raw_building_hint,
            'raw_floor_hint' => $raw_floor_hint,
            'raw_room_hint' => $raw_room_hint,
            'location_inference' => $loc,
        ]);
    }

    private function regexFirst(string $text, string $pattern): ?string
    {
        if (preg_match($pattern, $text, $m)) {
            return $m[1] ?? $m[0];
        }
        return null;
    }
}
