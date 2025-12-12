<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Interfaces\RoomInterface;
use App\Models\Room;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Support\Facades\DB;

class RoomService implements RoomInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait;

    /**
     * Store a newly created resource in storage.
     */
    public function storeRoom(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                $room = Room::create([
                    'room_name' => $data['room_name'] ?? null,
                    'floor_id' => $data['floor_id'] ?? null,
                    'file' => $data['file'] ?? null,
                ]);

                return $this->returnModel(201, 'success', 'Room created successfully!', $room, $room->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateRoom($roomId, array $data): array
    {
        try {
            return DB::transaction(function () use ($roomId, $data) {
                $room = Room::findOrFail($roomId);

                $room = tap($room)->update([
                    'room_name' => $data['room_name'] ?? $room->room_name,
                    'floor_id' => $data['floor_id'] ?? $room->floor_id,
                    'file' => $data['file'] ?? $room->file,
                ]);

                return $this->returnModel(200, 'success', 'Room updated successfully!', $room, $room->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deleteRoom($roomId): array
    {
        try {
            return DB::transaction(function () use ($roomId) {
                $room = Room::findOrFail($roomId);
                $room->delete();

                return $this->returnModel(200, 'success', 'Room deleted successfully!');
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }
}
