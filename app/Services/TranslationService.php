<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TranslationService
{
    private string $apiBase = 'https://api.openai.com/v1';

    /**
     * Get OpenAI API key
     */
    private function apiKey(): string
    {
        $key = config('services.openai.key') ?: env('OPENAI_API_KEY');
        if (!$key) {
            throw new \Exception('Missing OpenAI API key');
        }
        return $key;
    }

    /**
     * Make HTTP JSON request to OpenAI API
     */
    private function httpJson(string $url, array $payload, int $timeout = 60)
    {
        $resp = Http::withToken($this->apiKey())
            ->timeout($timeout)
            ->acceptJson()
            ->asJson()
            ->post($url, $payload);
            
        if ($resp->failed()) {
            Log::warning('OpenAI API error', [
                'url' => $url, 
                'status' => $resp->status(), 
                'body' => $resp->body()
            ]);
            throw new \Exception('Translation API error: ' . $resp->body());
        }
        
        return $resp->json();
    }

    /**
     * Translate text to English using OpenAI
     */
    public function translateToEnglish(string $text): string
    {
        // Return empty string if input is empty
        if (empty(trim($text))) {
            return $text;
        }

        // Cache translations to avoid repeated API calls for same text
        $cacheKey = 'translation_' . md5($text);
        
        return Cache::remember($cacheKey, 3600, function () use ($text) {
            try {
                $data = $this->httpJson($this->apiBase . '/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'temperature' => 0,
                    'max_tokens' => 500,
                    'messages' => [
                        [
                            'role' => 'system', 
                            'content' => 'You are a precise translation engine for emergency situations. Detect the language of the user text and output ONLY an accurate English translation. If the text is already English, output it unchanged. Preserve all important details about injuries, location, and urgency. No commentary or explanations.'
                        ],
                        [
                            'role' => 'user', 
                            'content' => $text
                        ]
                    ]
                ]);

                $translated = $data['choices'][0]['message']['content'] ?? $text;
                return trim($translated);

            } catch (\Exception $e) {
                Log::error('Translation failed', [
                    'text' => $text,
                    'error' => $e->getMessage()
                ]);
                
                // Return original text if translation fails
                return $text;
            }
        });
    }

    /**
     * Translate multiple text fields
     */
    public function translateFields(array $fields): array
    {
        $translated = [];
        
        foreach ($fields as $key => $value) {
            if (!empty($value) && is_string($value)) {
                $translated[$key] = $this->translateToEnglish($value);
            } else {
                $translated[$key] = $value;
            }
        }
        
        return $translated;
    }

    /**
     * Check if text appears to be in English (simple heuristic)
     */
    public function isLikelyEnglish(string $text): bool
    {
        // Simple check - if over 80% of words are common English words
        $commonWords = [
            'the', 'is', 'at', 'which', 'on', 'and', 'a', 'to', 'this', 'be', 
            'has', 'have', 'it', 'in', 'of', 'for', 'not', 'with', 'he', 'as', 
            'you', 'do', 'will', 'can', 'if', 'no', 'man', 'up', 'her', 'all', 
            'any', 'may', 'say', 'she', 'or', 'an', 'are', 'his', 'your', 'how',
            'help', 'need', 'emergency', 'hurt', 'pain', 'blood', 'injury', 'fire',
            'stuck', 'trapped', 'fell', 'broken', 'stairs', 'bathroom', 'room',
            'floor', 'building', 'urgent', 'please', 'quickly', 'fast', 'now'
        ];
        
        $words = preg_split('/\s+/', strtolower(trim($text)));
        $totalWords = count($words);
        
        if ($totalWords === 0) {
            return true; // Empty text is "English"
        }
        
        $englishWords = 0;
        foreach ($words as $word) {
            $cleanWord = preg_replace('/[^a-z]/', '', $word);
            if (in_array($cleanWord, $commonWords)) {
                $englishWords++;
            }
        }
        
        return ($englishWords / $totalWords) > 0.6; // 60% threshold
    }
}