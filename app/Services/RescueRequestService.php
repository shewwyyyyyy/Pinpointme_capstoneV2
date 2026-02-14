<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Interfaces\RescueRequestInterface;
use App\Models\RescueRequest;
use App\Services\TranslationService;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RescueRequestService implements RescueRequestInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait;
        
    protected $translationService;
    
    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function storeRescueRequest(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                // Translate text fields to English for rescuers
                $translatedData = $this->translateTextFields($data);
                
                $rescueRequest = RescueRequest::create([
                    'rescue_code' => $this->generateUniqueRescueCode(),
                    'assigned_rescuer' => $translatedData['assigned_rescuer'] ?? null,
                    'user_id' => $translatedData['user_id'] ?? null,
                    'status' => $translatedData['status'] ?? 'pending',
                    'building_id' => $translatedData['building_id'] ?? null,
                    'floor_id' => $translatedData['floor_id'] ?? null,
                    'room_id' => $translatedData['room_id'] ?? null,
                    'description' => $translatedData['description'] ?? null,
                    'mobility_status' => $translatedData['mobility_status'] ?? null,
                    'injuries' => $translatedData['injuries'] ?? null,
                    'urgency_level' => $translatedData['urgency_level'] ?? null,
                    'additional_info' => $translatedData['additional_info'] ?? null,
                    'firstName' => $translatedData['firstName'] ?? null,
                    'lastName' => $translatedData['lastName'] ?? null,
                ]);

                return $this->returnModel(201, 'success', 'Rescue request created successfully!', $rescueRequest, $rescueRequest->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateRescueRequest($rescueRequestId, array $data): array
    {
        try {
            return DB::transaction(function () use ($rescueRequestId, $data) {
                $rescueRequest = RescueRequest::findOrFail($rescueRequestId);

                $rescueRequest = tap($rescueRequest)->update([
                    'assigned_rescuer' => $data['assigned_rescuer'] ?? $rescueRequest->assigned_rescuer,
                    'status' => $data['status'] ?? $rescueRequest->status,
                    'description' => $data['description'] ?? $rescueRequest->description,
                    'mobility_status' => $data['mobility_status'] ?? $rescueRequest->mobility_status,
                    'injuries' => $data['injuries'] ?? $rescueRequest->injuries,
                    'urgency_level' => $data['urgency_level'] ?? $rescueRequest->urgency_level,
                    'additional_info' => $data['additional_info'] ?? $rescueRequest->additional_info,
                ]);

                return $this->returnModel(200, 'success', 'Rescue request updated successfully!', $rescueRequest, $rescueRequest->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deleteRescueRequest($rescueRequestId): array
    {
        try {
            return DB::transaction(function () use ($rescueRequestId) {
                $rescueRequest = RescueRequest::findOrFail($rescueRequestId);
                $rescueRequest->delete();

                return $this->returnModel(200, 'success', 'Rescue request deleted successfully!');
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Update status by rescue code.
     */
    public function updateStatus($rescueCode, array $data): array
    {
        try {
            return DB::transaction(function () use ($rescueCode, $data) {
                $rescueRequest = RescueRequest::where('rescue_code', $rescueCode)->firstOrFail();

                $rescueRequest = tap($rescueRequest)->update([
                    'status' => $data['status'] ?? $rescueRequest->status,
                    'assigned_rescuer' => $data['assigned_rescuer'] ?? $rescueRequest->assigned_rescuer,
                ]);

                return $this->returnModel(200, 'success', 'Rescue request status updated successfully!', $rescueRequest, $rescueRequest->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Mark rescue request as safe.
     */
    public function markSafe($rescueRequestId): array
    {
        try {
            return DB::transaction(function () use ($rescueRequestId) {
                $rescueRequest = RescueRequest::findOrFail($rescueRequestId);

                $rescueRequest = tap($rescueRequest)->update([
                    'status' => 'completed',
                ]);

                return $this->returnModel(200, 'success', 'Rescue request marked as safe!', $rescueRequest, $rescueRequest->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Translate text fields to English for rescuers
     */
    private function translateTextFields(array $data): array
    {
        try {
            $fieldsToTranslate = ['description', 'additional_info', 'injuries'];
            
            foreach ($fieldsToTranslate as $field) {
                if (!empty($data[$field]) && is_string($data[$field])) {
                    // Only translate if the text doesn't appear to be in English
                    if (!$this->translationService->isLikelyEnglish($data[$field])) {
                        Log::info('Translating emergency text', [
                            'field' => $field,
                            'original' => $data[$field]
                        ]);
                        
                        $translated = $this->translationService->translateToEnglish($data[$field]);
                        $data[$field] = $translated;
                        
                        Log::info('Translation completed', [
                            'field' => $field,
                            'translated' => $translated
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Translation failed during rescue request creation', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            // Continue with original data if translation fails
        }
        
        return $data;
    }
    
    /**
     * Generate unique rescue code.
     */
    private function generateUniqueRescueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (RescueRequest::where('rescue_code', $code)->exists());

        return $code;
    }
}
