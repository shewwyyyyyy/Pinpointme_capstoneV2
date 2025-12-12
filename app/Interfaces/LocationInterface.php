<?php

namespace App\Interfaces;

interface LocationInterface
{
    /**
     * Store a newly created resource in storage.
     */
    public function storeLocation(array $data): array;  

    /**
     * Update the specified resource in storage.
     */
    public function updateLocation($locationId, array $data): array;

    public function deleteLocation($locationId): array;

}