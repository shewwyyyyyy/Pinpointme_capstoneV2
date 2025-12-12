<?php

namespace App\Interfaces;

interface AuditTrailInterface
{
    /**
     * Store a newly created resource in storage.
     */
    public function storeAuditTrail(array $data): array;

    /**
     * Remove the specified resource from storage.
     */
    public function deleteAuditTrail($auditTrailId): array;
}
