<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Room;
use App\Models\RescueRequest;
use App\Models\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Admin Dashboard with analytics
     */
    public function dashboard(Request $request)
    {
        $timeFilter = $request->get('time_filter', 'week');
        $startDate = $this->getStartDate($timeFilter);

        // Get rescue request statistics
        $rescueRequests = RescueRequest::where('created_at', '>=', $startDate)->get();
        
        $statusCounts = [
            'total' => $rescueRequests->count(),
            'pending' => $rescueRequests->where('status', 'pending')->count(),
            'in_progress' => $rescueRequests->whereIn('status', ['accepted', 'in_progress', 'en_route'])->count(),
            'completed' => $rescueRequests->whereIn('status', ['completed', 'rescued'])->count(),
        ];

        // Rescues by building
        $rescuesByBuilding = RescueRequest::where('created_at', '>=', $startDate)
            ->selectRaw('building_id, COUNT(*) as count')
            ->groupBy('building_id')
            ->with('building')
            ->get()
            ->map(fn($item) => [
                'name' => $item->building?->name ?? 'Unknown',
                'count' => $item->count
            ]);

        // Rescuer statistics
        $rescuers = User::where('role', 'rescuer')->get();
        $rescuerStats = [
            'total' => $rescuers->count(),
            'available' => $rescuers->where('status', 'available')->count(),
            'on_rescue' => $rescuers->where('status', 'on_rescue')->count(),
            'off_duty' => $rescuers->where('status', 'off_duty')->count(),
        ];

        // Recent alerts (recent rescue requests)
        $recentAlerts = RescueRequest::with(['building', 'floor', 'room', 'requester'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'rescue_code' => $r->rescue_code,
                'status' => $r->status,
                'urgency_level' => $r->urgency_level,
                'location' => $r->building?->name . ' - ' . $r->floor?->floor_name . ' - ' . $r->room?->room_name,
                'requester_name' => $r->firstName ? "{$r->firstName} {$r->lastName}" : ($r->requester ? "{$r->requester->first_name} {$r->requester->last_name}" : 'Anonymous'),
                'created_at' => $r->created_at->toISOString(),
            ]);

        // User statistics
        $userStats = [
            'total' => User::where('role', '!=', 'admin')->where('role', '!=', 'rescuer')->count(),
            'by_role' => [
                'student' => User::where('role', 'student')->count(),
                'faculty' => User::where('role', 'faculty')->count(),
                'staff' => User::where('role', 'staff')->count(),
            ]
        ];

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => compact('statusCounts', 'rescuesByBuilding', 'rescuerStats', 'recentAlerts', 'userStats')
            ]);
        }

        return Inertia::render('Admin/Dashboard', compact('statusCounts', 'rescuesByBuilding', 'rescuerStats', 'recentAlerts', 'userStats'));
    }

    /**
     * Users management page
     */
    public function users(Request $request)
    {
        $query = User::where('role', '!=', 'admin')
            ->where('role', '!=', 'rescuer');

        if ($request->has('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 50));

        $stats = [
            'total' => User::where('role', '!=', 'admin')->where('role', '!=', 'rescuer')->count(),
            'by_role' => [
                'student' => User::where('role', 'student')->count(),
                'faculty' => User::where('role', 'faculty')->count(),
                'staff' => User::where('role', 'staff')->count(),
            ]
        ];

        // Get recent audit trail for users
        $auditTrail = AuditTrail::where('entity_type', 'user')
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $users,
                'stats' => $stats,
                'audit_trail' => $auditTrail
            ]);
        }

        return Inertia::render('Admin/Users', compact('users', 'stats', 'auditTrail'));
    }

    /**
     * Rescuers management page
     */
    public function rescuers(Request $request)
    {
        $query = User::where('role', 'rescuer');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $rescuers = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 50));

        $counts = [
            'total' => User::where('role', 'rescuer')->count(),
            'available' => User::where('role', 'rescuer')->where('status', 'available')->count(),
            'on_rescue' => User::where('role', 'rescuer')->where('status', 'on_rescue')->count(),
            'off_duty' => User::where('role', 'rescuer')->where('status', 'off_duty')->count(),
            'unavailable' => User::where('role', 'rescuer')->where('status', 'unavailable')->count(),
        ];

        // Get recent audit trail for rescuers
        $auditTrail = AuditTrail::where('entity_type', 'rescuer')
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $rescuers,
                'counts' => $counts,
                'audit_trail' => $auditTrail
            ]);
        }

        return Inertia::render('Admin/Rescuers', compact('rescuers', 'counts', 'auditTrail'));
    }

    /**
     * Buildings management page
     */
    public function buildings(Request $request)
    {
        $buildings = Building::with(['floors.rooms'])->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $buildings
            ]);
        }

        return Inertia::render('Admin/Buildings', compact('buildings'));
    }

    /**
     * Reports page
     */
    public function reports(Request $request)
    {
        $timeFilter = $request->get('time_filter', 'day');
        $statusFilter = $request->get('status_filter', 'all');
        $startDate = $this->getStartDate($timeFilter);

        $query = RescueRequest::with(['building', 'floor', 'room', 'requester', 'rescuer'])
            ->where('created_at', '>=', $startDate);

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $rescueRequests = $query->orderBy('created_at', 'desc')->get();

        // Process for reports
        $reportData = $rescueRequests->map(fn($r) => [
            'id' => $r->id,
            'rescue_code' => $r->rescue_code,
            'name' => $r->firstName ? "{$r->firstName} {$r->lastName}" : ($r->requester ? "{$r->requester->first_name} {$r->requester->last_name}" : 'Anonymous'),
            'time' => $r->created_at->format('h:i A'),
            'date' => $r->created_at->format('M d, Y'),
            'location' => $r->building?->name . ' - ' . $r->floor?->floor_name . ' - ' . $r->room?->room_name,
            'building' => $r->building?->name,
            'status' => $r->status,
            'urgency_level' => $r->urgency_level,
            'rescuer_name' => $r->rescuer ? "{$r->rescuer->first_name} {$r->rescuer->last_name}" : null,
        ]);

        $counts = [
            'total' => $rescueRequests->count(),
            'pending' => $rescueRequests->where('status', 'pending')->count(),
            'in_progress' => $rescueRequests->whereIn('status', ['accepted', 'in_progress', 'en_route'])->count(),
            'completed' => $rescueRequests->whereIn('status', ['completed', 'rescued'])->count(),
        ];

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $reportData,
                'counts' => $counts
            ]);
        }

        return Inertia::render('Admin/Reports', compact('reportData', 'counts'));
    }

    /**
     * Store a new user
     */
    public function storeUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:student,faculty,staff',
            'phone' => 'nullable|string',
            'student_id' => 'nullable|string',
            'faculty_id' => 'nullable|string',
            'staff_id' => 'nullable|string',
            'id_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Validate SDCA email domain (only in production)
        if (!AuthController::isValidSdcaEmail($request->email)) {
            return response()->json([
                'success' => false, 
                'errors' => ['email' => ['Only SDCA email addresses (@sdca.edu.ph) are allowed.']]
            ], 422);
        }

        // Generate a random temporary password
        $tempPassword = 'sdca' . rand(1000, 9999);
        
        // In local environment, create user as active for easier testing
        $isLocal = app()->environment('local', 'development');

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'role' => $request->role,
            'phone' => $request->phone,
            'student_id' => $request->student_id ?? $request->id_number,
            'faculty_id' => $request->faculty_id,
            'staff_id' => $request->staff_id,
            'password' => Hash::make($tempPassword),
            'status' => $isLocal ? 'active' : 'pending',
            'otp_verified' => $isLocal,
            'force_password_change' => true, // User must change password on first login
        ]);

        // Send OTP email if requested
        if ($request->send_otp) {
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->otp_code = $otp;
            $user->otp_expires_at = now()->addMinutes(30); // 30 minutes for initial setup
            $user->save();

            // Send welcome email with OTP
            try {
                \Mail::send([], [], function ($message) use ($user, $otp, $tempPassword) {
                    $message->to($user->email)
                        ->subject('Welcome to PinPointMe - Verify Your Account')
                        ->html("
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                                <div style='background: linear-gradient(135deg, #1976D2, #0D47A1); padding: 30px; text-align: center;'>
                                    <h1 style='color: white; margin: 0;'>Welcome to PinPointMe</h1>
                                </div>
                                <div style='padding: 30px; background: #f5f5f5;'>
                                    <h2 style='color: #333;'>Hello {$user->first_name}!</h2>
                                    <p style='color: #666; font-size: 16px;'>Your account has been created. Please verify your email to activate your account.</p>
                                    
                                    <div style='background: white; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: center;'>
                                        <p style='color: #888; margin-bottom: 10px;'>Your verification code is:</p>
                                        <h1 style='color: #1976D2; letter-spacing: 8px; font-size: 36px; margin: 10px 0;'>{$otp}</h1>
                                        <p style='color: #888; font-size: 12px;'>This code expires in 30 minutes</p>
                                    </div>
                                    
                                    <div style='background: #fff3e0; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                                        <p style='color: #e65100; margin: 0;'><strong>Your temporary password:</strong> {$tempPassword}</p>
                                        <p style='color: #666; font-size: 12px; margin: 5px 0 0;'>Please change this after logging in.</p>
                                    </div>
                                    
                                    <p style='color: #888; font-size: 14px;'>If you didn't request this, please ignore this email.</p>
                                </div>
                                <div style='background: #333; padding: 20px; text-align: center;'>
                                    <p style='color: #888; margin: 0; font-size: 12px;'>PinPointMe Emergency Response System</p>
                                </div>
                            </div>
                        ");
                });
            } catch (\Exception $e) {
                \Log::error('Failed to send welcome email: ' . $e->getMessage());
            }
        }

        // Record audit trail
        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'create',
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'description' => "Created user: {$user->first_name} {$user->last_name} (pending verification)",
        ]);

        // In local environment, include temp password in response for testing
        $responseData = ['success' => true, 'data' => $user, 'message' => 'User created successfully.'];
        if (app()->environment('local', 'development')) {
            $responseData['temp_password'] = $tempPassword;
            $responseData['message'] = "User created. Temp password: {$tempPassword}";
        }
        
        return response()->json($responseData);
    }

    /**
     * Update a user
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'role' => 'sometimes|in:student,faculty,staff,rescuer',
            'status' => 'sometimes|string',
            'phone' => 'nullable|string',
            'phone_number' => 'nullable|string',
            'contact_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Handle phone field from different sources (phone, phone_number, contact_number)
        $phoneValue = $request->phone ?? $request->phone_number ?? $request->contact_number ?? $user->phone;
        
        $updateData = $request->only(['first_name', 'last_name', 'email', 'role', 'status', 'student_id']);
        if ($phoneValue !== null) {
            $updateData['phone'] = $phoneValue;
        }
        
        $user->update($updateData);

        // Record audit trail
        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'update',
            'entity_type' => $user->role === 'rescuer' ? 'rescuer' : 'user',
            'entity_id' => $user->id,
            'description' => "Updated user: {$user->first_name} {$user->last_name}",
        ]);

        return response()->json(['success' => true, 'data' => $user]);
    }

    /**
     * Delete a user
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $name = "{$user->first_name} {$user->last_name}";
        $entityType = $user->role === 'rescuer' ? 'rescuer' : 'user';

        $user->delete();

        // Record audit trail
        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'delete',
            'entity_type' => $entityType,
            'entity_id' => $id,
            'description' => "Deleted user: {$name}",
        ]);

        return response()->json(['success' => true, 'message' => 'User deleted successfully']);
    }

    /**
     * Store a new rescuer
     */
    public function storeRescuer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Validate SDCA email domain (only in production)
        if (!AuthController::isValidSdcaEmail($request->email)) {
            return response()->json([
                'success' => false, 
                'errors' => ['email' => ['Only SDCA email addresses (@sdca.edu.ph) are allowed.']]
            ], 422);
        }

        // Generate a random temporary password
        $tempPassword = 'sdca' . rand(1000, 9999);
        
        // In local environment, create rescuer as available for easier testing
        $isLocal = app()->environment('local', 'development');

        $rescuer = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'role' => 'rescuer',
            'phone' => $request->phone,
            'password' => Hash::make($tempPassword),
            'status' => $isLocal ? 'available' : 'pending',
            'otp_verified' => $isLocal,
            'force_password_change' => true, // Rescuer must change password on first login
        ]);

        // Send OTP email if requested
        if ($request->send_otp) {
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $rescuer->otp_code = $otp;
            $rescuer->otp_expires_at = now()->addMinutes(30); // 30 minutes for initial setup
            $rescuer->save();

            // Send welcome email with OTP
            try {
                \Mail::send([], [], function ($message) use ($rescuer, $otp, $tempPassword) {
                    $message->to($rescuer->email)
                        ->subject('Welcome to PinPointMe - Rescuer Account Verification')
                        ->html("
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                                <div style='background: linear-gradient(135deg, #1976D2, #0D47A1); padding: 30px; text-align: center;'>
                                    <h1 style='color: white; margin: 0;'>Welcome to PinPointMe</h1>
                                    <p style='color: rgba(255,255,255,0.8); margin: 5px 0 0;'>Emergency Rescue Team</p>
                                </div>
                                <div style='padding: 30px; background: #f5f5f5;'>
                                    <h2 style='color: #333;'>Hello {$rescuer->first_name}!</h2>
                                    <p style='color: #666; font-size: 16px;'>You have been registered as a <strong>Rescuer</strong> in the PinPointMe Emergency Response System.</p>
                                    <p style='color: #666; font-size: 16px;'>Please verify your email to activate your account.</p>
                                    
                                    <div style='background: white; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: center;'>
                                        <p style='color: #888; margin-bottom: 10px;'>Your verification code is:</p>
                                        <h1 style='color: #1976D2; letter-spacing: 8px; font-size: 36px; margin: 10px 0;'>{$otp}</h1>
                                        <p style='color: #888; font-size: 12px;'>This code expires in 30 minutes</p>
                                    </div>
                                    
                                    <div style='background: #fff3e0; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                                        <p style='color: #e65100; margin: 0;'><strong>Your temporary password:</strong> {$tempPassword}</p>
                                        <p style='color: #666; font-size: 12px; margin: 5px 0 0;'>You will be required to change this after logging in.</p>
                                    </div>
                                    
                                    <div style='background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                                        <p style='color: #1565c0; margin: 0;'><strong>As a rescuer, you will:</strong></p>
                                        <ul style='color: #666; margin: 10px 0 0; padding-left: 20px;'>
                                            <li>Receive emergency rescue requests</li>
                                            <li>Respond to users in need of assistance</li>
                                            <li>Coordinate with the emergency response team</li>
                                        </ul>
                                    </div>
                                    
                                    <p style='color: #888; font-size: 14px;'>If you didn't expect this email, please contact the administrator.</p>
                                </div>
                                <div style='padding: 20px; background: #e0e0e0; text-align: center;'>
                                    <p style='color: #666; margin: 0; font-size: 12px;'>&copy; " . date('Y') . " PinPointMe - SDCA Emergency Rescue System</p>
                                </div>
                            </div>
                        ");
                });
            } catch (\Exception $e) {
                \Log::error('Failed to send rescuer welcome email: ' . $e->getMessage());
                // Still create the rescuer but note the email failure
            }
        }

        // Record audit trail
        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'create',
            'entity_type' => 'rescuer',
            'entity_id' => $rescuer->id,
            'description' => "Created rescuer: {$rescuer->first_name} {$rescuer->last_name}",
        ]);

        return response()->json(['success' => true, 'data' => $rescuer]);
    }

    /**
     * Helper to get start date based on time filter
     */
    private function getStartDate($timeFilter)
    {
        return match($timeFilter) {
            'day' => Carbon::now()->startOfDay(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfWeek(),
        };
    }
}
