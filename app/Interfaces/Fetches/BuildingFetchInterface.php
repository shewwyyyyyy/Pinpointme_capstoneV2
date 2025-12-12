<?php

namespace App\Interfaces\Fetches;

interface BuildingFetchInterface
{
    /**
     * Display a listing of the buildings.
     */
    public function index(array $request = [], bool $isPaginated = false, ?string $resourceClass = null): array;

    /**
     * Display the specified building.
     */
    public function show(int $buildingId): array;
}
