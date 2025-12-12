<?php

namespace App\Interfaces;

interface EmployeeInterface
{
    /**
     * Store a newly created resource in storage.
     */
    public function storeEmployee(array $data): array;

    /**
     * Update the specified resource in storage.
     */
    public function updateEmployee($employeeId, array $data): array;

    /**
     * Remove the specified resource from storage.
     */
    public function deleteEmployee($employeeId): array;
}
