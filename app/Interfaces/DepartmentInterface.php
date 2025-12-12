<?php

namespace App\Interfaces;

interface DepartmentInterface
{
    /**
     * Store a newly created resource in storage.
     */
    public function storeDepartment(array $data): array;


    /**
     * Update the specified resource in storage.
     */
    public function updateDepartment($departmentId, array $data): array;

    /**
     * Remove the specified resource from storage.
     */
    public function deleteDepartment($departmentId): array;
}
