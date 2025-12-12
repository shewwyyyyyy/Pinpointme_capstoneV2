<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Interfaces\FloorInterface;
use App\Models\Floor;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Support\Facades\DB;

class FloorService implements FloorInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait;

    /**
     * Store a newly created resource in storage.
     */
    public function storeFloor(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                $floor = Floor::create([
                    'floor_name' => $data['floor_name'] ?? null,
                    'building_id' => $data['building_id'] ?? null,
                    'floor_plan_url' => $data['floor_plan_url'] ?? null,
                ]);

                return $this->returnModel(201, 'success', 'Floor created successfully!', $floor, $floor->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateFloor($floorId, array $data): array
    {
        try {
            return DB::transaction(function () use ($floorId, $data) {
                $floor = Floor::findOrFail($floorId);

                $floor = tap($floor)->update([
                    'floor_name' => $data['floor_name'] ?? $floor->floor_name,
                    'building_id' => $data['building_id'] ?? $floor->building_id,
                    'floor_plan_url' => $data['floor_plan_url'] ?? $floor->floor_plan_url,
                ]);

                return $this->returnModel(200, 'success', 'Floor updated successfully!', $floor, $floor->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deleteFloor($floorId): array
    {
        try {
            return DB::transaction(function () use ($floorId) {
                $floor = Floor::findOrFail($floorId);
                
                // Delete related rooms
                $floor->rooms()->delete();
                $floor->delete();

                return $this->returnModel(200, 'success', 'Floor deleted successfully!');
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Add room to floor.
     */
    public function addRoom($floorId, array $data): array
    {
        try {
            return DB::transaction(function () use ($floorId, $data) {
                $floor = Floor::findOrFail($floorId);
                
                $room = $floor->rooms()->create([
                    'room_name' => $data['room_name'] ?? null,
                    'file' => $data['file'] ?? null,
                ]);

                return $this->returnModel(201, 'success', 'Room added successfully!', $room, $room->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }
}
