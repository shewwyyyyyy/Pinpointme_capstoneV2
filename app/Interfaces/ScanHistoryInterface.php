<?php

namespace App\Interfaces;

interface ScanHistoryInterface
{
    /**
     * Store a newly created scan history in storage.
     */
    public function storeScanHistory(array $data): array;

    /**
     * Update the specified scan history in storage.
     */
    public function updateScanHistory($scanHistoryId, array $data): array;

    /**
     * Remove the specified scan history from storage.
     */
    public function deleteScanHistory($scanHistoryId): array;
}
