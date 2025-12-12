<?php

namespace App\Services\Fetches;

use App\Helpers\Helper;
use App\Interfaces\Fetches\ScanHistoryFetchInterface;
use App\Models\ScanHistory;
use App\Traits\DefaultPaginateFilterTrait;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Pagination\Paginator;

class ScanHistoryFetchService implements ScanHistoryFetchInterface
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
        // Load profile with department, and property relationships
        $query = ScanHistory::query()->with(['profile.department', 'property']);

        if ($resourceClass !== null && isset($resourceClass::$relations)) {
            $query->with($resourceClass::$relations ?? []);
        }

        //Search filter - search by employee name, unique identifier, meal schedule
        if (isset($request['search']) && !empty($request['search'])) {
            $search = $request['search'];
            $query->where(function ($q) use ($search) {
                $q->where('meal_schedule', 'like', "%{$search}%")
                  ->orWhereHas('profile', function ($profileQuery) use ($search) {
                      $profileQuery->where('first_name', 'like', "%{$search}%")
                                   ->orWhere('last_name', 'like', "%{$search}%")
                                   ->orWhere('middle_name', 'like', "%{$search}%")
                                   ->orWhere('unique_identifier', 'like', "%{$search}%");
                  });
            });
        }

        // Position filter - filter by OJT or Employee
        if (isset($request['position']) && !empty($request['position'])) {
            $query->whereHas('profile', function ($profileQuery) use ($request) {
                $profileQuery->where('position', $request['position']);
            });
        }

        // Date filter
        if (isset($request['date']) && !empty($request['date'])) {
            $query->whereDate('scanned_at', $request['date']);
        }

        // Time filter
        if (isset($request['time']) && !empty($request['time'])) {
            $query->whereTime('scanned_at', $request['time']);
        }

        if ($isPaginated) {
            $allowedFields = (new ScanHistory())->getFillable();

            [
                'per_page' => $per_page,
                'sort_by' => $sort_by,
                'sort' => $sort,
                'current_page' => $current_page
            ] = $this->paginateFilter($request, $allowedFields);

            // Manually set the current page
            Paginator::currentPageResolver(fn() => $current_page ?? 1);

            // Default sort by most recent scans first
            $sortBy = $sort_by ?? 'scanned_at';
            $sortOrder = $sort ?? 'desc';

            $scanHistories = $query->orderBy($sortBy, $sortOrder)->paginate($per_page);
        } else {
            $scanHistories = $query->orderBy('scanned_at', 'desc')->get();
        }

        return $this->returnModelCollection(200, Helper::SUCCESS, 'Successfully fetched!', $scanHistories);
    }

    /**
     * Display the specified scan history.
     */
    public function show(int $scanHistoryId): array
    {
        $scanHistory = ScanHistory::find($scanHistoryId);

        return $this->returnModel(200, Helper::SUCCESS, 'Scan history retrieved successfully!', $scanHistory);
    }
}
