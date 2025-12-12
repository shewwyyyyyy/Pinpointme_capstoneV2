<?php

namespace App\Http\Controllers;

use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class BuildingController extends Controller
{
    /**
     * Display buildings (Admin page)
     *
     * @return void
     */
    public function index()
    {
        // Check if request expects JSON (API call)
        if (request()->expectsJson() || request()->is('api/*')) {
            return $this->apiIndex();
        }

        $isAdmin = Auth::check() && Auth::user()->isAdmin;

        if (!$isAdmin) {
            return Inertia::render('Error', [
                'code' => 404,
                'message' => 'This page unauthorized to access'
            ]);
        }

        return Inertia::render('System/Buildings', [
            'can' => []
        ]);
    }

    /**
     * API: Get all buildings with floors and rooms
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiIndex()
    {
        $buildings = Building::with(['floors.rooms'])->get();
        return response()->json($buildings);
    }

    /**
     * Store Building
     *
     * @param Request $request
     * @return void
     */
    public function store(Request $request)
    {
        $data = $request->only('name');
        
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        Building::create($data);

        return redirect()->back()->with('success', 'Building created successfully');
    }

    /**
     * Update building
     *
     * @param Request $request
     * @param int $id
     * @return void
     */
    public function update(Request $request, $id)
    {
        $data = $request->only('name');
        
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        $building = Building::findOrFail($id);
        $building->update($data);

        return redirect()->back()->with('success', 'Building updated successfully');
    }

    /**
     * Delete building
     *
     * @param int $id
     * @return void
     */
    public function destroy($id)
    {
        $building = Building::findOrFail($id);

        DB::transaction(function () use ($building) {
            $building->floors()->with('rooms')->get()->each(function ($floor) {
                $floor->rooms->each(function ($room) {
                    $room->delete();
                });
                $floor->delete();
            });
            $building->delete();
        });

        return redirect()->back()->with('success', 'Building deleted successfully');
    }

    /**
     * Add floor to building
     *
     * @param Request $request
     * @param int $id
     * @return void
     */
    public function addFloor(Request $request, $id)
    {
        $data = $request->only('floor_name');
        
        $validator = Validator::make($data, [
            'floor_name' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        $building = Building::findOrFail($id);
        $building->floors()->create($data);

        return redirect()->back()->with('success', 'Floor added successfully');
    }
}
