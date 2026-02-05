<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class RoomController extends Controller
{
    /**
     * Display rooms
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

        return Inertia::render('System/Rooms', [
            'can' => []
        ]);
    }

    /**
     * Store Room
     *
     * @param Request $request
     * @return void
     */
    public function store(Request $request)
    {
        $data = $request->only('room_name', 'floor_id', 'file');
        
        $validator = Validator::make($data, [
            'room_name' => 'required|string|max:255',
            'floor_id' => 'required|exists:floors,id',
            'file' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        Room::create($data);

        return redirect()->back()->with('success', 'Room created successfully');
    }

    /**
     * Update room
     *
     * @param Request $request
     * @param int $id
     * @return void
     */
    public function update(Request $request, $id)
    {
        $data = $request->only('room_name', 'floor_id', 'file');
        
        $validator = Validator::make($data, [
            'room_name' => 'required|string|max:255',
            'floor_id' => 'required|exists:floors,id',
            'file' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        $room = Room::findOrFail($id);
        $room->update($data);

        return redirect()->back()->with('success', 'Room updated successfully');
    }

    /**
     * Delete room
     *
     * @param int $id
     * @return void
     */
    public function destroy($id)
    {
        $room = Room::findOrFail($id);
        $room->delete();

        return redirect()->back()->with('success', 'Room deleted successfully');
    }

    /**
     * API: Get room details for QR code scanning
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $room = Room::with(['floor.building'])->findOrFail($id);
        
        return response()->json([
            'room' => [
                'id' => $room->id,
                'room_name' => $room->room_name,
                'floor_id' => $room->floor_id,
                'floor_name' => $room->floor->floor_name,
                'building_id' => $room->floor->building->id,
                'building_name' => $room->floor->building->name,
            ]
        ]);
    }

    /**
     * API: Update QR code data for a room
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateQrCode(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'qr_data' => 'required|array',
            'qr_data.building_id' => 'required|integer',
            'qr_data.building_name' => 'required|string',
            'qr_data.floor_id' => 'required|integer',
            'qr_data.floor_name' => 'required|string',
            'qr_data.room_id' => 'required|integer',
            'qr_data.room_name' => 'required|string',
            'qr_data.version' => 'required',
            'qr_data.unique_hash' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid QR data',
                'details' => $validator->errors()
            ], 422);
        }

        $room = Room::findOrFail($id);
        
        // Store QR data and mark previous versions as invalid
        $room->update([
            'qr_data' => json_encode($request->qr_data),
            'qr_updated_at' => now(),
            'qr_version' => $request->qr_data['version'] ?? time(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'QR code data updated successfully - previous codes invalidated',
            'qr_data' => $request->qr_data,
            'room_id' => $room->id,
            'invalidated_previous' => true
        ]);
    }

    /**
     * API: Validate a scanned QR code
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateQrCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|integer',
            'version' => 'required',
            'unique_hash' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'error' => 'Invalid QR data format',
                'details' => $validator->errors()
            ], 422);
        }

        $room = Room::with(['floor.building'])->find($request->room_id);
        
        if (!$room) {
            return response()->json([
                'valid' => false,
                'error' => 'Room not found',
                'message' => 'This QR code refers to a room that no longer exists.'
            ], 404);
        }

        // Check if QR version matches current version
        $currentVersion = $room->qr_version;
        $scannedVersion = $request->version;
        
        // If room has a stored version, ALWAYS validate against it
        if ($currentVersion !== null) {
            // Convert both to strings for comparison (handle integer/string mismatch)
            if (strval($currentVersion) !== strval($scannedVersion)) {
                return response()->json([
                    'valid' => false,
                    'error' => 'QR code expired',
                    'message' => 'This QR code is outdated. The room information has been updated. Please use the new QR code.',
                    'room_name' => $room->room_name,
                    'current_version' => $currentVersion,
                    'scanned_version' => $scannedVersion
                ], 410); // 410 Gone - resource has been updated
            }
        }
        
        // QR is valid - return room details
        return response()->json([
            'valid' => true,
            'message' => 'QR code is valid',
            'room' => [
                'id' => $room->id,
                'room_name' => $room->room_name,
                'floor_id' => $room->floor_id,
                'floor_name' => $room->floor->floor_name,
                'building_id' => $room->floor->building->id,
                'building_name' => $room->floor->building->name,
            ]
        ]);
    }
}
