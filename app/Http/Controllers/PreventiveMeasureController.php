<?php

namespace App\Http\Controllers;

use App\Models\PreventiveMeasure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PreventiveMeasureController extends Controller
{
    /**
     * Display a listing of preventive measures (API)
     */
    public function index(Request $request)
    {
        $query = PreventiveMeasure::active()->ordered();

        // Filter by category if provided
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        $measures = $query->get()->map(function ($measure) {
            return [
                'id' => $measure->id,
                'title' => $measure->title,
                'description' => $measure->description,
                'author' => $measure->author,
                'thumbnail' => $measure->thumbnail,
                'thumbnail_url' => $measure->thumbnail_full_url,
                'video_url' => $measure->video_full_url,
                'video_path' => $measure->video_path,
                'category' => $measure->category,
                'created_at' => $measure->created_at->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $measures,
        ]);
    }

    /**
     * Display preventive measures page for users (Inertia)
     */
    public function userIndex(Request $request)
    {
        $query = PreventiveMeasure::active()->ordered();

        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        $measures = $query->get()->map(function ($measure) {
            return [
                'id' => $measure->id,
                'title' => $measure->title,
                'description' => $measure->description,
                'author' => $measure->author,
                'thumbnail' => $measure->thumbnail,
                'thumbnail_url' => $measure->thumbnail_full_url,
                'video_url' => $measure->video_full_url,
                'video_path' => $measure->video_path,
                'category' => $measure->category,
                'created_at' => $measure->created_at->toISOString(),
            ];
        });

        return Inertia::render('User/PreventiveMeasure', [
            'measures' => $measures,
        ]);
    }

    /**
     * Get all preventive measures for admin
     */
    public function adminIndex(Request $request)
    {
        $query = PreventiveMeasure::ordered();

        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        $measures = $query->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $measures,
            ]);
        }

        return Inertia::render('Admin/PreventiveMeasures', [
            'measures' => $measures,
        ]);
    }

    /**
     * Store a newly created preventive measure
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'author' => 'nullable|string|max:255',
            'thumbnail' => 'nullable|image|max:5120', // 5MB max
            'thumbnail_url' => 'nullable|url',
            'video' => 'nullable|mimes:mp4,mov,avi,webm|max:102400', // 100MB max
            'video_url' => 'nullable|url',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $request->file('thumbnail')->store('preventive-measures/thumbnails', 'public');
        }

        // Handle video upload
        if ($request->hasFile('video')) {
            $validated['video_path'] = $request->file('video')->store('preventive-measures/videos', 'public');
        }

        $measure = PreventiveMeasure::create($validated);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Preventive measure created successfully',
                'data' => $measure,
            ], 201);
        }

        return redirect()->back()->with('success', 'Preventive measure created successfully');
    }

    /**
     * Display the specified preventive measure
     */
    public function show($id)
    {
        $measure = PreventiveMeasure::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $measure->id,
                'title' => $measure->title,
                'description' => $measure->description,
                'author' => $measure->author,
                'thumbnail' => $measure->thumbnail,
                'thumbnail_url' => $measure->thumbnail_full_url,
                'video_url' => $measure->video_full_url,
                'video_path' => $measure->video_path,
                'category' => $measure->category,
                'created_at' => $measure->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * Update the specified preventive measure
     */
    public function update(Request $request, $id)
    {
        $measure = PreventiveMeasure::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'author' => 'nullable|string|max:255',
            'thumbnail' => 'nullable|image|max:5120',
            'thumbnail_url' => 'nullable|url',
            'video' => 'nullable|mimes:mp4,mov,avi,webm|max:102400',
            'video_url' => 'nullable|url',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail
            if ($measure->thumbnail) {
                Storage::disk('public')->delete($measure->thumbnail);
            }
            $validated['thumbnail'] = $request->file('thumbnail')->store('preventive-measures/thumbnails', 'public');
        }

        // Handle video upload
        if ($request->hasFile('video')) {
            // Delete old video
            if ($measure->video_path && !str_starts_with($measure->video_path, 'http')) {
                Storage::disk('public')->delete($measure->video_path);
            }
            $validated['video_path'] = $request->file('video')->store('preventive-measures/videos', 'public');
        }

        $measure->update($validated);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Preventive measure updated successfully',
                'data' => $measure->fresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Preventive measure updated successfully');
    }

    /**
     * Remove the specified preventive measure
     */
    public function destroy($id)
    {
        $measure = PreventiveMeasure::findOrFail($id);

        // Delete associated files
        if ($measure->thumbnail && !str_starts_with($measure->thumbnail, 'http')) {
            Storage::disk('public')->delete($measure->thumbnail);
        }
        if ($measure->video_path && !str_starts_with($measure->video_path, 'http')) {
            Storage::disk('public')->delete($measure->video_path);
        }

        $measure->delete();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Preventive measure deleted successfully',
            ]);
        }

        return redirect()->back()->with('success', 'Preventive measure deleted successfully');
    }

    /**
     * Get available categories
     */
    public function categories()
    {
        $categories = PreventiveMeasure::distinct()
            ->whereNotNull('category')
            ->pluck('category');

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }
}
