<?php

namespace App\Http\Controllers;

use App\Interfaces\Fetches\ScanHistoryFetchInterface;
use App\Interfaces\ScanHistoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ScanHistoryController extends Controller
{
    public function __construct(
        private ScanHistoryInterface $scanHistory,
        private ScanHistoryFetchInterface $scanHistoryFetch
    ) {}

    /**
     * Display scan histories
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

        return Inertia::render('System/ScanHistories', [
            'can' => []
        ]);
    }

    /**
     * Store Scan History
     *
     * @param Request $request
     * @return void
     */
    public function store(Request $request)
    {
        $result = $this->scanHistory->storeScanHistory($request->all());

        $status = $result['status'] ?? 'error';
        $message = $result['message'] ?? 'An error occurred';

        return redirect()->back()->with($status, $message);
    }

    /**
     * Update scan history
     *
     * @param Request $request
     * @param [type] $id
     * @return void
     */
    public function update(Request $request, $id)
    {
        $result = $this->scanHistory->updateScanHistory($id, $request->all());

        $status = $result['status'] ?? 'error';
        $message = $result['message'] ?? 'An error occurred';

        return redirect()->back()->with($status, $message);
    }

    /**
     * Delete scan history
     *
     * @param [type] $id
     * @return void
     */
    public function destroy($id)
    {
        $result = $this->scanHistory->deleteScanHistory($id);

        $status = $result['status'] ?? 'error';
        $message = $result['message'] ?? 'An error occurred';

        return redirect()->back()->with($status, $message);
    }
}
