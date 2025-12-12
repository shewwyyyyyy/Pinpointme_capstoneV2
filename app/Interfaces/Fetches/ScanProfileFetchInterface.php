<?php

namespace App\Interfaces\Fetches;

interface ScanProfileFetchInterface
{
    /**
     * Display a listing of the scan histories.
     */
    public function indexScanProfile(array $request = [], bool $isPaginated = false, ?string $resourceClass = null): array;

    /**
     * Display the specified scan history.
     */
    public function showByUniqueIdentifier(int $uniqueIdentifier): array;
}
