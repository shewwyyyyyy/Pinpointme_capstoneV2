<?php

namespace App\Http\Controllers;

use App\Interfaces\LocationInterface;
use App\Interfaces\Fetches\PropertyFetchInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class LocationController extends Controller
{
    public function __construct(
        private LocationInterface $location,
        private PropertyFetchInterface $propertyFetch
    ) {}

    /**
     * Display locations
     *
     * @return void
     */
    public function index()
    {
        $isAdmin = Auth::user()->isAdmin;

        if (!$isAdmin) {
            return Inertia::render('Error', [
                'code' => 404,
                'message' => 'This page unauthorized to access'
            ]);
        }

        // Fetch properties for dropdown usage - simple data structure for instant loading
        $properties = Cache::remember('location_properties_simple', 300, function () {
            return [
                'data' => \App\Models\Property::select(['id', 'name', 'code', 'description'])->get(),
                'status' => 'success',
                'message' => 'Properties retrieved successfully'
            ];
        });

        return Inertia::render('System/Locations', [
            'can' => [],
            'properties' => $properties['data']
        ]);
    }

    /**
     * Store Location
     *
     * @param Request $request
     * @return void
     */
    public function store(Request $request)
    {
        $result = $this->location->storeLocation($request->all());

        $status = $result['status'] ?? 'error';
        $message = $result['message'] ?? 'An error occurred';

        return redirect()->back()->with($status, $message);
    }

    /**
     * Update location
     *
     * @param Request $request
     * @param [type] $id
     * @return void
     */
    public function update(Request $request, $id)
    {

        $result = $this->location->updateLocation($id, $request->all());

        $status = $result['status'] ?? 'error';

        $message = $result['message'] ?? 'An error occurred';

        return redirect()->back()->with($status, $message);
    }

    /**
     * Delete location
     *
     * @param [type] $id
     * @return void
     */
    public function destroy($id)
    {
        $result = $this->location->deleteLocation($id);

        $status = $result['status'] ?? 'error';
        $message = $result['message'] ?? 'An error occurred';

        return redirect()->back()->with($status, $message);
    }
}
