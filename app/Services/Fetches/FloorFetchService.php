<?php

namespace App\Services\Fetches;

use App\Helpers\Helper;
use App\Interfaces\Fetches\FloorFetchInterface;
use App\Models\Floor;
use App\Traits\DefaultPaginateFilterTrait;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Pagination\Paginator;

class FloorFetchService implements FloorFetchInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait,
        DefaultPaginateFilterTrait;

    /**
     * Display a listing of the floors.
     */
    public function index(array $request = [], bool $isPaginated = false, ?string $resourceClass = null): array
    {
        $query = Floor::query();

        if ($resourceClass !== null && isset($resourceClass::$relations)) {
            $query->with($resourceClass::$relations ?? []);
        } else {
            $query->with(['building', 'rooms']);
        }

        // Search filter
        if (isset($request['search']) && !empty($request['search'])) {
            $search = $request['search'];
            $query->where(function ($q) use ($search) {
                $q->where('floor_name', 'like', "%{$search}%");
            });
        }

        // Building filter
        if (isset($request['building_id']) && !empty($request['building_id'])) {
            $query->where('building_id', $request['building_id']);
        }

        if ($isPaginated) {
            $allowedFields = (new Floor())->getFillable();

            [
                'per_page' => $per_page,
                'sort_by' => $sort_by,
                'sort' => $sort,
                'current_page' => $current_page
            ] = $this->paginateFilter($request, $allowedFields);

            Paginator::currentPageResolver(fn() => $current_page ?? 1);

            $floors = $query->orderBy($sort_by, $sort)->paginate($per_page);
        } else {
            $floors = $query->get();
        }

        return $this->returnModelCollection(200, Helper::SUCCESS, 'Successfully fetched!', $floors);
    }

    /**
     * Display the specified floor.
     */
    public function show(int $floorId): array
    {
        $floor = Floor::with(['building', 'rooms'])->find($floorId);

        return $this->returnModel(200, Helper::SUCCESS, 'Floor retrieved successfully!', $floor);
    }
}
