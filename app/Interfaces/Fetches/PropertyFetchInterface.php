<?php

namespace App\Interfaces\Fetches;

interface PropertyFetchInterface
{
    /**
     * Display a listing of the properties.
     */
    public function index(array $request = [], bool $isPaginated = false, ?string $resourceClass = null): array;

    /**
     * Display the specified property.
     */
    public function show(int $propertyId): array;
}
