<?php

namespace App\Services\Fetches;

use App\Helpers\Helper;
use App\Interfaces\Fetches\AuditTrailFetchInterface;
use App\Models\AuditTrail;
use App\Traits\DefaultPaginateFilterTrait;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Pagination\Paginator;

class AuditTrailFetchService implements AuditTrailFetchInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait,
        DefaultPaginateFilterTrait;

    /**
     * Display a listing of the audit trails.
     */
    public function index(array $request = [], bool $isPaginated = false, ?string $resourceClass = null): array
    {
        $query = AuditTrail::query();

        if ($resourceClass !== null && isset($resourceClass::$relations)) {
            $query->with($resourceClass::$relations ?? []);
        }

        // Search filter
        if (isset($request['search']) && !empty($request['search'])) {
            $search = $request['search'];
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('initiator', 'like', "%{$search}%")
                  ->orWhere('account_updated', 'like', "%{$search}%")
                  ->orWhere('details', 'like', "%{$search}%");
            });
        }

        // Action filter
        if (isset($request['action']) && !empty($request['action'])) {
            $query->where('action', $request['action']);
        }

        // Initiator filter
        if (isset($request['initiator']) && !empty($request['initiator'])) {
            $query->where('initiator', $request['initiator']);
        }

        // Initiator role filter
        if (isset($request['initiator_role']) && !empty($request['initiator_role'])) {
            $query->where('initiator_role', $request['initiator_role']);
        }

        if ($isPaginated) {
            $allowedFields = (new AuditTrail())->getFillable();

            [
                'per_page' => $per_page,
                'sort_by' => $sort_by,
                'sort' => $sort,
                'current_page' => $current_page
            ] = $this->paginateFilter($request, $allowedFields);

            Paginator::currentPageResolver(fn() => $current_page ?? 1);

            $auditTrails = $query->orderBy($sort_by, $sort)->paginate($per_page);
        } else {
            $auditTrails = $query->orderByDesc('created_at')->get();
        }

        return $this->returnModelCollection(200, Helper::SUCCESS, 'Successfully fetched!', $auditTrails);
    }

    /**
     * Display the specified audit trail.
     */
    public function show(int $auditTrailId): array
    {
        $auditTrail = AuditTrail::find($auditTrailId);

        return $this->returnModel(200, Helper::SUCCESS, 'Audit trail retrieved successfully!', $auditTrail);
    }
}
