<?php

namespace App\Interfaces;

interface UserInterface
{
    /**
     * Store a newly created resource in storage.
     */
    public function storeUser(array $data): array;

    /**
     * Update the specified resource in storage.
     */
    public function updateUser($userId, array $data): array;

    /**
     * Remove the specified resource from storage.
     */
    public function deleteUser($userId): array;
}
