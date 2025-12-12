<?php

namespace App\Http\Controllers;

use App\Models\Floor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class FloorController extends Controller
{
    /**
     * Display floors
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

        return Inertia::render('System/Floors', [
            'can' => []
        ]);
    }

    /**
     * Store Floor
     *
     * @param Request $request
     * @return void
     */
    public function store(Request $request)
    {
        $data = $request->only('floor_name', 'building_id', 'floor_plan_url');
        
        $validator = Validator::make($data, [
            'floor_name' => 'required|string|max:255',
            'building_id' => 'required|exists:buildings,id',
            'floor_plan_url' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        Floor::create($data);

        return redirect()->back()->with('success', 'Floor created successfully');
    }

    /**
     * Update floor
     *
     * @param Request $request
     * @param int $id
     * @return void
     */
    public function update(Request $request, $id)
    {
        $data = $request->only('floor_name', 'building_id', 'floor_plan_url');
        
        $validator = Validator::make($data, [
            'floor_name' => 'sometimes|string|max:255',
            'building_id' => 'sometimes|exists:buildings,id',
            'floor_plan_url' => 'sometimes|string|nullable'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        $floor = Floor::findOrFail($id);
        $floor->update($data);

        return redirect()->back()->with('success', 'Floor updated successfully');
    }

    /**
     * Delete floor
     *
     * @param int $id
     * @return void
     */
    public function destroy($id)
    {
        $floor = Floor::findOrFail($id);
        
        $floor->rooms()->each(function ($room) {
            $room->delete();
        });
        $floor->delete();

        return redirect()->back()->with('success', 'Floor deleted successfully');
    }

    /**
     * Add room to floor
     *
     * @param Request $request
     * @param int $id
     * @return void
     */
    public function addRoom(Request $request, $id)
    {
        $data = $request->only('room_name', 'file');
        
        $validator = Validator::make($data, [
            'room_name' => 'required|string|max:255',
            'file' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        $floor = Floor::findOrFail($id);
        $floor->rooms()->create($data);

        return redirect()->back()->with('success', 'Room added successfully');
    }
}
