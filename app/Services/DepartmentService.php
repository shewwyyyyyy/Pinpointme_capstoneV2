<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Interfaces\DepartmentInterface;
use App\Models\Department;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DepartmentService implements DepartmentInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait;

    /**
     * Store a newly created resource in storage.
     */
    public function storeDepartment(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                $department = Department::create([
                    'name' => $data['name'] ?? null,
                    'code' => $data['code'] ?? null,
                    'description' => $data['description'] ?? null,
                ]);

                return $this->returnModel(201, 'success', 'Department created successfully!', $department, $department->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateDepartment($departmentId, array $data): array
    {
        try {
            return DB::transaction(function () use ($departmentId, $data) {
                $department = Department::findOrFail($departmentId);

                $department = tap($department)->update([
                    'name' => $data['name'] ?? $department->name,
                    'code' => $data['code'] ?? $department->code,
                    'description' => $data['description'] ?? $department->description,
                ]);

                return $this->returnModel(200, 'success', 'Department updated successfully!', $department, $department->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deleteDepartment($departmentId): array
    {
        try {
            return DB::transaction(function () use ($departmentId) {
                $department = Department::findOrFail($departmentId);
                $department->delete();

                return $this->returnModel(200, 'success', 'Department deleted successfully!');
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }
}
