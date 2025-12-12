<?php

namespace App\Interfaces\Fetches;

interface LocationFetchInterface
{
    /**
     * Display a listing of the locations.
     */
    public function index(array $request = [], bool $isPaginated = false, ?string $resourceClass = null): array;

    /**
     * Display the specified location.
     */
    public function show(int $locationId): array;
}