<?php

namespace App\Interfaces;

interface PropertyInterface
{
    /**
     * Store a newly created property in storage.
     */
    public function storeProperty(array $data): array;

    /**
     * Update the specified property in storage.
     */
    public function updateProperty($propertyId, array $data): array;

    /**
     * Remove the specified property from storage.
     */
    public function deleteProperty($propertyId): array;
}
