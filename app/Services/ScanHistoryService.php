<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Interfaces\ScanHistoryInterface;
use App\Models\ScanHistory;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Support\Facades\DB;

class ScanHistoryService implements ScanHistoryInterface

{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait;

    /**
     * Store a newly created scan history in storage.
     */
    public function storeScanHistory(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                $scanHistory = ScanHistory::create([
                    'profile_id' => $data['profile_id'],
                    'scanned_at' => $data['scanned_at'],
                    'property_id' => $data['property_id'],
                    'meal_schedule' => $data['meal_schedule'],
                    'start_date' => $data['start_date'] ?? null,
                    'end_date' => $data['end_date'] ?? null,
                    'meal_count' => $data['meal_count'] ?? 0,
                ]);

                return $this->returnModel(201, Helper::SUCCESS, 'Scan history created successfully!', $scanHistory, $scanHistory->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, Helper::ERROR, $th->getMessage());
        }
    }

    /**
     * Update the specified scan history in storage.
     */
    public function updateScanHistory($scanHistoryId, array $data): array
    {
        try {
            return DB::transaction(function () use ($scanHistoryId, $data) {
                $scanHistory = ScanHistory::findOrFail($scanHistoryId);

                $scanHistory = tap($scanHistory)->update([
                    'profile_id' => $data['profile_id'] ?? $scanHistory->profile_id,
                    'scanned_at' => $data['scanned_at'] ?? $scanHistory->scanned_at,
                    'property_id' => $data['property_id'] ?? $scanHistory->property_id,
                    'meal_schedule' => $data['meal_schedule'] ?? $scanHistory->meal_schedule,
                    'start_date' => $data['start_date'] ?? $scanHistory->start_date,
                    'end_date' => $data['end_date'] ?? $scanHistory->end_date,
                    'meal_count' => $data['meal_count'] ?? $scanHistory->meal_count,
                ]);

                return $this->returnModel(200, Helper::SUCCESS, 'Scan history updated successfully!', $scanHistory, $scanHistory->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, Helper::ERROR, $th->getMessage());
        }
    }

    /**
     * Remove the specified scan history from storage.
     */
    public function deleteScanHistory($scanHistoryId): array
    {
        try {
            return DB::transaction(function () use ($scanHistoryId) {
                $scanHistory = ScanHistory::findOrFail($scanHistoryId);
                $scanHistory->delete();

                return $this->returnModel(200, Helper::SUCCESS, 'Scan history deleted successfully!');
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, Helper::ERROR, $th->getMessage());
        }
    }
}
