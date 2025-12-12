<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentFormRequest;
use App\Interfaces\DepartmentInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function __construct(
        private DepartmentInterface $department
    ) {}


    /**
     * Display departments
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

        return Inertia::render('System/Departments', [
            'can' => []
        ]);
    }

    /**
     * Store Department
     *
     * @param Request $request
     * @return void
     */
    public function store(DepartmentFormRequest $request)
    {
        $result = $this->department->storeDepartment($request->all());

        $status = $result['status'] ?? 'error';
        $message = $result['message'] ?? 'An error occurred';

        return redirect()->back()->with($status, $message);
    }

    /**
     * Update department
     *
     * @param Request $request
     * @param [type] $id
     * @return void
     */
    public function update(Request $request, $id)
    {
        $result = $this->department->updateDepartment($id, $request->all());

        $status = $result['status'] ?? 'error';
        $message = $result['message'] ?? 'An error occurred';

        return redirect()->back()->with($status, $message);
    }


    /**
     * Delete department
     *
     * @param [type] $id
     * @return void
     */
    public function destroy($id)
    {
        $result = $this->department->deleteDepartment($id);

        $status = $result['status'] ?? 'error';
        $message = $result['message'] ?? 'An error occurred';

        return redirect()->back()->with($status, $message);
    }
}
