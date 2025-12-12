<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScanFormRequest;
use App\Interfaces\ScanProcessInterface;
use App\Services\ScanProcessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ScanController extends Controller
{
    public function __construct(
        private ScanProcessInterface $scanProcess
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $isAdmin = Auth::user()->isAdmin;

        if ($isAdmin) {
            return Inertia::render('Error', [
                'code' => 404,
                'message' => 'This page unauthorized to access'
            ]);
        }

        return Inertia::render('Scans', [
            'can' => []
        ]);
    }

    /**
     * Handle the scan action.
     */
    public function scan(Request $request)
    {
        $result = $this->scanProcess->processScan($request->validate());

        $status = $result['status'] ?? 'error';
        $message = $result['message'] ?? 'An error occurred';

        return redirect()->back()->with($status, $message);
    }
}
