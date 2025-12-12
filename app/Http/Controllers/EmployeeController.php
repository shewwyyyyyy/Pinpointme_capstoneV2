<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Http\Requests\EmployeeFormRequest;
use App\Interfaces\Fetches\DepartmentFetchInterface;
use App\Interfaces\Fetches\LocationFetchInterface;
use App\Interfaces\Fetches\PropertyFetchInterface;
use App\Interfaces\ProfileInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    public function __construct(
        private ProfileInterface $profile,
        private DepartmentFetchInterface $departmentFetch,
        private PropertyFetchInterface $propertyFetch,
        private LocationFetchInterface $locationFetch
    ) {}


    /**
     * Display Employee
     *
     * @return void
     */
    public function index()
    {
        $isAdmin = Auth::user()->isAdmin;

        if (!$isAdmin) {
            return Inertia::render('Error', [
                'code' => 404,
                'message' => 'This page unauthorized to access this page'
            ]);
        }

        $departments = Cache::remember('profile_departments', 60, function () {
            return $this->departmentFetch->index();
        });

        $properties = Cache::remember('profile_properties', 60, function () {
            return $this->propertyFetch->index();
        });

        $locations = Cache::remember('profile_locations', 60, function () {
            return $this->locationFetch->index();
        });


        return Inertia::render('System/Employees', [
            'can' => [],
            'departments' => $departments['data'],
            'properties' => $properties['data'],
            'locations' => $locations['data'],
            'positions' => Helper::PROFILE_POSITIONS,
        ]);
    }

    /**
     * Store Employee
     *
     * @param Request $request
     * @return void
     */
    public function store(EmployeeFormRequest $request)
    {
        $result = $this->profile->storeProfile($request->all());

        $status = $result['status'] ?? 'error';
        $message = $result['message'] ?? 'An error occurred';

        return redirect()->back()->with($status, $message);
    }

    /**
     * Update Employee
     *
     * @param Request $request
     * @param [type] $id
     * @return void
     */
    public function update(Request $request, $id)
    {
        $result = $this->profile->updateProfile($id, $request->all());

        $status = $result['status'] ?? 'error';
        $message = $result['message'] ?? 'An error occurred';

        return redirect()->back()->with($status, $message);
    }


    /**
     * Delete Employee
     *
     * @param [type] $id
     * @return void
     */
    public function destroy($id)
    {
        $result = $this->profile->deleteProfile($id);

        $status = $result['status'] ?? 'error';
        $message = $result['message'] ?? 'An error occurred';

        return redirect()->back()->with($status, $message);
    }
}
