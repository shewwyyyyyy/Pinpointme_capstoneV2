<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Interfaces\LocationInterface;
use App\Models\Location;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Support\Facades\DB;

class LocationService implements LocationInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait;

    /**
     * Store a newly created location in storage.
     */
    public function storeLocation(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                $location = Location::create([
                    'name' => $data['name'],
                    'code' => $data['code'],
                    'description' => $data['description'],
                    'property_id' => $data['property_id'] ?? 1
                ]);

                return $this->returnModel(201, Helper::SUCCESS, 'location created successfully!', $location, $location->id);
            });
        } catch (\Exception $e) {
            $code = $this->httpCode($e);
            return $this->returnModel($code, Helper::ERROR, $e->getMessage());
        }
    }

    /**
     * Update the specified property in storage.
     */
    public function updateLocation($locationId, array $data): array
    {
        try {
            return DB::transaction(function () use ($locationId, $data) {
                $location = Location::findOrFail($locationId);

                $location = tap($location)->update([
                    'name' => $data['name'] ?? $location->name,
                    'code' => $data['code'] ?? $location->code,
                    'description' => $data['description'] ?? $location->description,
                    'property_id' => $data['property_id'] ?? $location->property_id
                ]);

                return $this->returnModel(200, Helper::SUCCESS, 'Location updated successfully!', $location, $location->id);
            });
        } catch (\Exception $e) {
            $code = $this->httpCode($e);
            return $this->returnModel($code, Helper::ERROR, $e->getMessage());
        }
    }

    /**
     * Remove the specified property from storage.
     */
    public function deleteLocation($locationId): array
    {
        try {
            return DB::transaction(function () use ($locationId) {
                $location = location::findOrFail($locationId);
                $location->delete();

                return $this->returnModel(200, Helper::SUCCESS, 'location deleted successfully!');
            });
        } catch (\Exception $e) {
            $code = $this->httpCode($e);
            return $this->returnModel($code, Helper::ERROR, $e->getMessage());
        }
    }
}
