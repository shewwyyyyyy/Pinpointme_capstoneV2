<?php

namespace App\Services\Fetches;

use App\Helpers\Helper;
use App\Interfaces\Fetches\DepartmentFetchInterface;
use App\Models\Department;
use App\Traits\DefaultPaginateFilterTrait;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Pagination\Paginator;

class DepartmentFetchService implements DepartmentFetchInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait,
        DefaultPaginateFilterTrait;

    /**
     * Display a listing of the departments.
     */
    public function index(array $request = [], bool $isPaginated = false, ?string $resourceClass = null): array
    {
        $query = Department::query();

        if ($resourceClass !== null && isset($resourceClass::$relations)) {
            $query->with($resourceClass::$relations ?? []);
        }

        //Search filter
        if (isset($request['search']) && !empty($request['search'])) {
            $search = $request['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($isPaginated) {
            $allowedFields = (new Department())->getFillable();

            [
                'per_page' => $per_page,
                'sort_by' => $sort_by,
                'sort' => $sort,
                'current_page' => $current_page
            ] = $this->paginateFilter($request, $allowedFields);

            // Manually set the current page
            Paginator::currentPageResolver(fn() => $current_page ?? 1);

            $departments = $query->orderBy($sort_by, $sort)->paginate($per_page);
        } else {
            $departments = $query->get();
        }

        return $this->returnModelCollection(200, Helper::SUCCESS, 'Successfully fetched!', $departments);
    }

    /**
     * Display the specified department.
     */
    public function show(int $departmentId): array
    {
        $department = Department::find($departmentId);

        return $this->returnModel(200, Helper::SUCCESS, 'Department retrieved successfully!', $department);
    }
}