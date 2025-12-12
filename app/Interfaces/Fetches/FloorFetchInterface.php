<?php

namespace App\Interfaces\Fetches;

interface FloorFetchInterface
{
    /**
     * Display a listing of the floors.
     */
    public function index(array $request = [], bool $isPaginated = false, ?string $resourceClass = null): array;

    /**
     * Display the specified floor.
     */
    public function show(int $floorId): array;
}
