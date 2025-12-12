<?php

namespace App\Interfaces;

interface RoomInterface
{
    /**
     * Store a newly created resource in storage.
     */
    public function storeRoom(array $data): array;

    /**
     * Update the specified resource in storage.
     */
    public function updateRoom($roomId, array $data): array;

    /**
     * Remove the specified resource from storage.
     */
    public function deleteRoom($roomId): array;
}
