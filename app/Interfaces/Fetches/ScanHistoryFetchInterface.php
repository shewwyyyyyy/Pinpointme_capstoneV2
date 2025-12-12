<?php

namespace App\Interfaces\Fetches;

interface ScanHistoryFetchInterface
{
    /**
     * Display a listing of the scan histories.
     */
    public function index(array $request = [], bool $isPaginated = false, ?string $resourceClass = null): array;

    /**
     * Display the specified scan history.
     */
    public function show(int $scanHistoryId): array;
}
