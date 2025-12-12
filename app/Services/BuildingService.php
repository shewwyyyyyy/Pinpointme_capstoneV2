<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Interfaces\BuildingInterface;
use App\Models\Building;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Support\Facades\DB;

class BuildingService implements BuildingInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait;

    /**
     * Store a newly created resource in storage.
     */
    public function storeBuilding(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                $building = Building::create([
                    'name' => $data['name'] ?? null,
                ]);

                return $this->returnModel(201, 'success', 'Building created successfully!', $building, $building->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateBuilding($buildingId, array $data): array
    {
        try {
            return DB::transaction(function () use ($buildingId, $data) {
                $building = Building::findOrFail($buildingId);

                $building = tap($building)->update([
                    'name' => $data['name'] ?? $building->name,
                ]);

                return $this->returnModel(200, 'success', 'Building updated successfully!', $building, $building->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deleteBuilding($buildingId): array
    {
        try {
            return DB::transaction(function () use ($buildingId) {
                $building = Building::findOrFail($buildingId);
                
                // Delete related floors and rooms
                $building->floors()->each(function ($floor) {
                    $floor->rooms()->delete();
                    $floor->delete();
                });
                
                $building->delete();

                return $this->returnModel(200, 'success', 'Building deleted successfully!');
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Add floor to building.
     */
    public function addFloor($buildingId, array $data): array
    {
        try {
            return DB::transaction(function () use ($buildingId, $data) {
                $building = Building::findOrFail($buildingId);
                
                $floor = $building->floors()->create([
                    'floor_name' => $data['floor_name'] ?? null,
                    'floor_plan_url' => $data['floor_plan_url'] ?? null,
                ]);

                return $this->returnModel(201, 'success', 'Floor added successfully!', $floor, $floor->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }
}
