<?php

namespace App\Services\Fetches;

use App\Helpers\Helper;
use App\Interfaces\Fetches\LocationFetchInterface;
use App\Models\Location;
use App\Traits\DefaultPaginateFilterTrait;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Pagination\Paginator;

class LocationFetchService implements LocationFetchInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait,
        DefaultPaginateFilterTrait;

    /**
     * Display a listing of the scan histories.
     */
    public function index(array $request = [], bool $isPaginated = false, ?string $resourceClass = null): array
    {
        $query = Location::query();

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
            $allowedFields = (new Location())->getFillable();

            [
                'per_page' => $per_page,
                'sort_by' => $sort_by,
                'sort' => $sort,
                'current_page' => $current_page
            ] = $this->paginateFilter($request, $allowedFields);

            // Manually set the current page
            Paginator::currentPageResolver(fn() => $current_page ?? 1);

            $locations = $query->orderBy($sort_by, $sort)->paginate($per_page);
        } else {

            $locations = $query->get();
        }

        return $this->returnModelCollection(200, Helper::SUCCESS, 'Successfully fetched!', $locations);
    }

    /**
     * Display the specified scan history.
     */
    public function show(int $locationId): array
    {
        $location = Location::find($locationId);

        return $this->returnModel(200, Helper::SUCCESS, 'Location retrieved successfully!', $location);
    }
}