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
}
