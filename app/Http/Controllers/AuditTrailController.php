<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuditTrailController extends Controller
{
    /**
     * Display audit trails
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

        return Inertia::render('System/AuditTrails', [
            'can' => []
        ]);
    }

    /**
     * Store Audit Trail
     *
     * @param Request $request
     * @return void
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'action' => 'required|string',
            'initiator' => 'required|string',
            'initiator_role' => 'nullable|string',
            'account_updated' => 'required|string',
            'details' => 'nullable|string',
        ]);

        AuditTrail::create($data);

        return redirect()->back()->with('success', 'Audit trail recorded successfully');
    }

    /**
     * Delete audit trail
     *
     * @param int $id
     * @return void
     */
    public function destroy($id)
    {
        $auditTrail = AuditTrail::findOrFail($id);
        $auditTrail->delete();

        return redirect()->back()->with('success', 'Audit trail deleted successfully');
    }

    /**
     * Record audit trail helper
     *
     * @param string $action
     * @param string $initiator
     * @param string|null $initiatorRole
     * @param string $accountUpdated
     * @param string $details
     * @return void
     */
    public static function recordAudit(
        string $action,
        string $initiator,
        ?string $initiatorRole,
        string $accountUpdated,
        string $details = ''
    ): void {
        AuditTrail::create([
            'action' => $action,
            'initiator' => $initiator,
            'initiator_role' => $initiatorRole,
            'account_updated' => $accountUpdated,
            'details' => $details,
        ]);
    }
}
