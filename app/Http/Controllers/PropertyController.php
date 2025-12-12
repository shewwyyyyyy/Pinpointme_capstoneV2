<?php

namespace App\Http\Controllers;

use App\Http\Requests\PropertyFormRequest;
use App\Interfaces\PropertyInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PropertyController extends Controller
{
    public function __construct(
        private PropertyInterface $property
    ) {}

    /**
     * Display properties
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

        return Inertia::render('System/Properties', [
            'can' => []
        ]);
    }

    /**
     * Store Property
     *
     * @param PropertyFormRequest $request
     * @return void
     */
    public function store(PropertyFormRequest $request)
    {
        $result = $this->property->storeProperty($request->all());

        $status = $result['status'] ?? 'error';
        $message = $result['message'] ?? 'An error occurred';

        return redirect()->back()->with($status, $message);
    }

    /**
     * Update property
     *
     * @param PropertyFormRequest $request
     * @param [type] $id
     * @return void
     */
    public function update(PropertyFormRequest $request, $id)
    {

        $result = $this->property->updateProperty($id, $request->all());

        $status = $result['status'] ?? 'error';

        $message = $result['message'] ?? 'An error occurred';

        return redirect()->back()->with($status, $message);
    }

    /**
     * Delete property
     *
     * @param [type] $id
     * @return void
     */
    public function destroy($id)
    {
        $result = $this->property->deleteProperty($id);

        $status = $result['status'] ?? 'error';
        $message = $result['message'] ?? 'An error occurred';

        return redirect()->back()->with($status, $message);
    }
}
