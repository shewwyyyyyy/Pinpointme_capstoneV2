<?php

namespace App\Http\Controllers;

use App\Models\Floor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class FloorPlanController extends Controller
{
    /**
     * Get floor plan data for a specific floor
     *
     * @param int $floorId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($floorId)
    {
        $floor = Floor::with('building', 'rooms')->findOrFail($floorId);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $floor->id,
                'floor_name' => $floor->floor_name,
                'building_id' => $floor->building_id,
                'building_name' => $floor->building->name ?? '',
                'floor_plan_url' => $floor->floor_plan_url,
                'floor_plan_data' => $floor->floor_plan_data,
                'rooms' => $floor->rooms->map(function ($room) {
                    return [
                        'id' => $room->id,
                        'room_name' => $room->room_name
                    ];
                })
            ]
        ]);
    }

    /**
     * Upload floor plan image
     *
     * @param Request $request
     * @param int $floorId
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImage(Request $request, $floorId)
    {
        $validator = Validator::make($request->all(), [
            'floor_plan' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10240' // Max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $floor = Floor::findOrFail($floorId);

        // Delete old image if exists
        if ($floor->floor_plan_url) {
            $oldPath = str_replace('/storage/', '', $floor->floor_plan_url);
            Storage::disk('public')->delete($oldPath);
        }

        // Store new image
        $path = $request->file('floor_plan')->store('floor-plans', 'public');
        $url = '/storage/' . $path;

        $floor->update(['floor_plan_url' => $url]);

        return response()->json([
            'success' => true,
            'message' => 'Floor plan uploaded successfully',
            'data' => [
                'floor_plan_url' => $url
            ]
        ]);
    }

    /**
     * Save floor plan annotations (room boxes and evacuation paths)
     *
     * @param Request $request
     * @param int $floorId
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveAnnotations(Request $request, $floorId)
    {
        $validator = Validator::make($request->all(), [
            'floor_plan_data' => 'required|array',
            'floor_plan_data.rooms' => 'nullable|array',
            'floor_plan_data.rooms.*.id' => 'nullable|integer',
            'floor_plan_data.rooms.*.room_id' => 'nullable|integer',
            'floor_plan_data.rooms.*.room_name' => 'nullable|string',
            'floor_plan_data.rooms.*.x' => 'required|numeric',
            'floor_plan_data.rooms.*.y' => 'required|numeric',
            'floor_plan_data.rooms.*.width' => 'required|numeric',
            'floor_plan_data.rooms.*.height' => 'required|numeric',
            'floor_plan_data.rooms.*.color' => 'nullable|string',
            'floor_plan_data.evacuation_paths' => 'nullable|array',
            'floor_plan_data.evacuation_paths.*.id' => 'nullable',
            'floor_plan_data.evacuation_paths.*.name' => 'nullable|string',
            'floor_plan_data.evacuation_paths.*.room_id' => 'nullable|integer',
            'floor_plan_data.evacuation_paths.*.points' => 'required|array',
            'floor_plan_data.evacuation_paths.*.points.*.x' => 'required|numeric',
            'floor_plan_data.evacuation_paths.*.points.*.y' => 'required|numeric',
            'floor_plan_data.evacuation_paths.*.color' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $floor = Floor::findOrFail($floorId);
        $floor->update(['floor_plan_data' => $request->input('floor_plan_data')]);

        return response()->json([
            'success' => true,
            'message' => 'Floor plan annotations saved successfully',
            'data' => [
                'floor_plan_data' => $floor->floor_plan_data
            ]
        ]);
    }

    /**
     * Delete floor plan image and annotations
     *
     * @param int $floorId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteFloorPlan($floorId)
    {
        $floor = Floor::findOrFail($floorId);

        // Delete image if exists
        if ($floor->floor_plan_url) {
            $path = str_replace('/storage/', '', $floor->floor_plan_url);
            Storage::disk('public')->delete($path);
        }

        $floor->update([
            'floor_plan_url' => null,
            'floor_plan_data' => null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Floor plan deleted successfully'
        ]);
    }

    /**
     * Render the floor plan editor page
     *
     * @param int $floorId
     * @return \Inertia\Response
     */
    public function editor($floorId)
    {
        $floor = Floor::with('building', 'rooms')->findOrFail($floorId);

        return Inertia::render('Admin/FloorPlanEditor', [
            'floor' => [
                'id' => $floor->id,
                'floor_name' => $floor->floor_name,
                'building_id' => $floor->building_id,
                'building_name' => $floor->building->name ?? '',
                'floor_plan_url' => $floor->floor_plan_url,
                'floor_plan_data' => $floor->floor_plan_data,
                'rooms' => $floor->rooms->map(function ($room) {
                    return [
                        'id' => $room->id,
                        'room_name' => $room->room_name
                    ];
                })
            ]
        ]);
    }
}
