<?php

namespace App\Interfaces;

interface RescueRequestInterface
{
    /**
     * Store a newly created resource in storage.
     */
    public function storeRescueRequest(array $data): array;

    /**
     * Update the specified resource in storage.
     */
    public function updateRescueRequest($rescueRequestId, array $data): array;

    /**
     * Remove the specified resource from storage.
     */
    public function deleteRescueRequest($rescueRequestId): array;

    /**
     * Update status by rescue code.
     */
    public function updateStatus($rescueCode, array $data): array;

    /**
     * Mark rescue request as safe.
     */
    public function markSafe($rescueRequestId): array;
}
