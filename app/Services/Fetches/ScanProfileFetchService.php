<?php

namespace App\Services\Fetches;

use App\Helpers\Helper;
use App\Interfaces\Fetches\ScanProfileFetchInterface;
use App\Models\Profile;
use App\Models\ScanHistory;
use App\Traits\DefaultPaginateFilterTrait;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Pagination\Paginator;

class ScanProfileFetchService implements ScanProfileFetchInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait,
        DefaultPaginateFilterTrait;

    /**
     * Display a listing of the scan histories.
     */
    public function indexScanProfile(array $request = [], bool $isPaginated = false, ?string $resourceClass = null): array
    {
        $query = Profile::query();

        if ($resourceClass !== null && isset($resourceClass::$relations)) {
            $query->with($resourceClass::$relations ?? []);
        }

        //Search filter
        if (isset($request['search']) && !empty($request['search'])) {
            $search = $request['search'];
            $query->where(function ($q) use ($search) {
                $q->where('unique_identifier', "=", "{$search}");
            });
        }


        if (isset($request['property_id']) && !empty($request['property_id'])) {
            $propertyId = $request['property_id'];
            $query->where(function ($q) use ($propertyId) {
                $q->where('property_id', "=", "{$propertyId}");
            });
        }

        if ($isPaginated) {
            $allowedFields = (new Profile())->getFillable();

            [
                'per_page' => $per_page,
                'sort_by' => $sort_by,
                'sort' => $sort,
                'current_page' => $current_page
            ] = $this->paginateFilter($request, $allowedFields);

            // Manually set the current page
            Paginator::currentPageResolver(fn() => $current_page ?? 1);

            $scanHistories = $query->orderBy($sort_by, $sort)->paginate($per_page);
        } else {

            $scanHistories = $query->get();
        }

        return $this->returnModelCollection(200, Helper::SUCCESS, 'Successfully fetched!', $scanHistories);
    }

    /**
     * Display the specified scan history.
     */
    public function showByUniqueIdentifier(int $uniqueIdentifier): array
    {
        $scanProfile = Profile::query()->where('unique_identifier', $uniqueIdentifier)->first();

        if ($scanProfile) {
            return $this->returnModel(200, Helper::SUCCESS, 'Scan history retrieved successfully!', $scanProfile);
        }

        return $this->returnModel(200, Helper::SUCCESS, 'Profile not found');
    }
}
