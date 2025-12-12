<?php

namespace App\Interfaces;

interface BuildingInterface
{
    /**
     * Store a newly created resource in storage.
     */
    public function storeBuilding(array $data): array;

    /**
     * Update the specified resource in storage.
     */
    public function updateBuilding($buildingId, array $data): array;

    /**
     * Remove the specified resource from storage.
     */
    public function deleteBuilding($buildingId): array;

    /**
     * Add floor to building.
     */
    public function addFloor($buildingId, array $data): array;
}
