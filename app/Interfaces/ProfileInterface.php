<?php

namespace App\Interfaces;

interface ProfileInterface
{
    /**
     * Store a newly created resource in storage.
     */
    public function storeProfile(array $data): array;

    /**
     * Update the specified resource in storage.
     */
    public function updateProfile($profileId, array $data): array;

    /**
     * Remove the specified resource from storage.
     */
    public function deleteProfile($profileId): array;
}
