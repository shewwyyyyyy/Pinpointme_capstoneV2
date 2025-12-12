<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ScanProfileResource;
use App\Interfaces\Fetches\ScanProfileFetchInterface;
use App\Interfaces\ScanProcessInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScanProfileApiController extends Controller
{
    public function __construct(
        private ScanProfileFetchInterface $scanProfileFetch,
        private ScanProcessInterface $scanProcess
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Set default pagination and sorting
        $request->merge([
            'per_page' => $request->get('per_page', 10),
            'sort_by' => $request->get('sort_by', 'id'),
            'sort' => $request->get('sort', 'desc'),
        ]);

        $results = $this->scanProfileFetch->indexScanProfile($request->toArray(), true);

        $data = $results['data'];
        $code = $results['code'];
        $status = $results['status'];
        $message = $results['message'];

        return response()->json([
            'data' => ScanProfileResource::collection($data->all()),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'total' => $data->total(),
            'last_page' => $data->lastPage(),
            'search' => $request->input('search'),
            'sort_by' => $request->input('sort_by'),
            'sort_direction' => $request->input('sort'),
            'code' => $code,
            'status' => $status,
            'message' => $message
        ], $code);
    }

    /**
     * Display a listing of the resource.
     */
    public function storeUniqueIdentifiers(Request $request, string $uniqueIdentifier)
    {

        $data = [
            'unique_identifier' => $uniqueIdentifier,
            'propertyId' => $request->input('property_id')
        ];

        $result = $this->scanProcess->processScan($data);

        $data = $result['data'] ?? null;
        $code = $result['code'];
        $status = $result['status'];
        $message = $result['message'];

        return response()->json([
            'data' => $data,
            'code' => $code,
            'status' => $status,
            'message' => $message
        ], $code);
    }
}
