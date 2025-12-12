<?php

namespace App\Interfaces;

interface AccountInterface
{
    /**
     * Store a new user account.
     */
    public function storeAccount(array $data): array;

    /**
     * Update an existing user account.
     */
    public function updateAccount(array $data, int $profileId): array;

    /**
     * Delete a user account and its associated profile.
     */
    public function deleteAccount(int $profileId): array;
}
