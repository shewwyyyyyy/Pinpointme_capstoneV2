<?php

namespace App\Interfaces;

interface FloorInterface
{
    /**
     * Store a newly created resource in storage.
     */
    public function storeFloor(array $data): array;

    /**
     * Update the specified resource in storage.
     */
    public function updateFloor($floorId, array $data): array;

    /**
     * Remove the specified resource from storage.
     */
    public function deleteFloor($floorId): array;

    /**
     * Add room to floor.
     */
    public function addRoom($floorId, array $data): array;
}
