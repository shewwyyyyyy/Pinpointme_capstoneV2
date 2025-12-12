<?php

namespace App\Http\Controllers;

use App\DTOs\ChangePasswordDTO;
use App\Helpers\ErrorHelper;
use App\Helpers\Helper;
use App\Http\Requests\LoginFormRequest;
use App\Http\Requests\System\ChangePasswordFormRequest;
use App\Interfaces\ActivityLogInterface;
use App\Interfaces\AuthInterface;
use App\Interfaces\CurrentUserInterface;
use App\Interfaces\ManageAccountInterface;
use App\Models\User;
use App\Traits\ActivityLoggerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Carbon\Carbon;

class AuthController extends Controller
{

    public function __construct(
        private AuthInterface $auth,
        private CurrentUserInterface $currentUser,
        // private ManageAccountInterface $manageAccount,
        // private ActivityLogInterface $activityLog,
    ) {}

    /**
     * show login page
     */
    public function index()
    {
        return Inertia::render('Login');
    }

    /**
     * attempt login
     */
    public function login(Request $request)
    {
        // Handle pure API requests (not Inertia) with JSON response
        $isApiRequest = ($request->is('api/*') || $request->header('Accept') === 'application/json') 
                        && !$request->header('X-Inertia');
        
        if ($isApiRequest) {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid input data',
                    'errors' => $validator->errors()
                ], 422);
            }

            $credentials = $request->only('email', 'password');
            
            // For API requests, validate credentials and create token
            $user = User::where('email', $credentials['email'])->first();
            
            if ($user && Hash::check($credentials['password'], $user->password)) {
                // Create Sanctum token
                $token = $user->createToken('api-token')->plainTextToken;
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Login successful',
                    'token' => $token,
                    'id' => $user->id,
                    'email' => $user->email,
                    'first_name' => $user->first_name ?? '',
                    'last_name' => $user->last_name ?? '',
                    'role' => $user->role ?? 'student',
                    'isAdmin' => $user->isAdmin ?? false,
                    'username' => $user->username ?? '',
                    'profile_picture' => $user->profile_picture ?? null,
                    'contact_number' => $user->contact_number ?? '',
                    'data' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'first_name' => $user->first_name ?? '',
                        'last_name' => $user->last_name ?? '',
                        'role' => $user->role ?? 'student',
                        'username' => $user->username ?? '',
                        'isAdmin' => $user->isAdmin ?? false,
                        'profile_picture' => $user->profile_picture ?? null,
                        'contact_number' => $user->contact_number ?? '',
                    ]
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Handle form/Inertia requests
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        // Support both email and username login
        $loginField = filter_var($request->email, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [
            $loginField => $request->email,
            'password' => $request->password
        ];
        
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            
            // Update last login timestamp
            $user->update(['last_login_at' => Carbon::now()]);
            
            // Check if account is pending verification
            if ($user->status === 'pending' && !$user->otp_verified) {
                // Log out and redirect to verification
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                // Resend OTP if needed
                $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $user->update([
                    'otp_code' => $otp,
                    'otp_expires_at' => now()->addMinutes(30),
                ]);
                
                // Send OTP email
                try {
                    Mail::send([], [], function ($message) use ($user, $otp) {
                        $message->to($user->email)
                            ->subject('PinPointMe - Verification Code')
                            ->html("
                                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                                    <div style='background: linear-gradient(135deg, #1976D2, #0D47A1); padding: 30px; text-align: center;'>
                                        <h1 style='color: white; margin: 0;'>PinPointMe</h1>
                                    </div>
                                    <div style='padding: 30px; background: #f5f5f5;'>
                                        <h2 style='color: #333;'>Account Verification Required</h2>
                                        <p style='color: #666;'>Hi {$user->first_name}, please verify your account with this code:</p>
                                        <div style='background: white; padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0;'>
                                            <h1 style='color: #1976D2; letter-spacing: 8px; font-size: 36px; margin: 0;'>{$otp}</h1>
                                            <p style='color: #888; font-size: 12px; margin-top: 10px;'>Code expires in 30 minutes</p>
                                        </div>
                                    </div>
                                </div>
                            ");
                    });
                } catch (\Exception $e) {
                    \Log::error('Failed to send verification OTP: ' . $e->getMessage());
                }
                
                return redirect('/verify-account?email=' . urlencode($user->email));
            }
            
            // Check if user needs to change password (forced password change)
            if ($user->force_password_change) {
                return redirect('/change-password');
            }
            
            // Redirect based on user role
            // Admin check - either isAdmin flag is true or role is 'admin'
            if ($user->isAdmin == 1 || $user->isAdmin === true || $user->role === 'admin') {
                return redirect()->intended('/admin/dashboard');
            } 
            // Rescuer check
            elseif ($user->role === 'rescuer') {
                return redirect()->intended('/rescuer/dashboard');
            } 
            // All other users (student, faculty, staff, etc.) go to user scanner
            else {
                return redirect()->intended('/user/scanner');
            }
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }



    /**
     * attempt logout
     */
    public function logout(Request $request)
    {
        // Clear session and log out user
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // For Inertia requests, redirect properly
        if ($request->header('X-Inertia')) {
            return redirect('/login');
        }

        return redirect('/login');
    }

    /**
     * Generate and send OTP to user's email
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid email'], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store OTP with expiration (5 minutes)
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(5),
            'otp_verified' => false,
        ]);

        // Send OTP via email
        try {
            Mail::send([], [], function ($message) use ($user, $otp) {
                $message->to($user->email)
                    ->subject('PinPointMe - Your OTP Verification Code')
                    ->html("
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                            <div style='background: #1976D2; color: white; padding: 20px; text-align: center;'>
                                <h1 style='margin: 0;'>PinPointMe</h1>
                                <p style='margin: 5px 0 0 0;'>Emergency Rescue System</p>
                            </div>
                            <div style='padding: 30px; background: #f9f9f9;'>
                                <h2 style='color: #333;'>Your Verification Code</h2>
                                <p style='color: #666;'>Hello {$user->first_name},</p>
                                <p style='color: #666;'>Use the following OTP code to verify your login:</p>
                                <div style='background: #1976D2; color: white; font-size: 32px; font-weight: bold; text-align: center; padding: 20px; letter-spacing: 8px; margin: 20px 0;'>
                                    {$otp}
                                </div>
                                <p style='color: #999; font-size: 12px;'>This code will expire in 5 minutes.</p>
                                <p style='color: #999; font-size: 12px;'>If you didn't request this code, please ignore this email.</p>
                            </div>
                            <div style='padding: 15px; background: #eee; text-align: center; font-size: 12px; color: #666;'>
                                &copy; " . date('Y') . " PinPointMe - SDCA Emergency Rescue System
                            </div>
                        </div>
                    ");
            });

            return response()->json([
                'success' => true, 
                'message' => 'OTP sent successfully to your email',
                'expires_in' => 300 // 5 minutes in seconds
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send OTP email: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to send OTP email'], 500);
        }
    }

    /**
     * Verify OTP code
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid input'], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // Check if OTP matches and hasn't expired
        if ($user->otp_code !== $request->otp) {
            return response()->json(['success' => false, 'message' => 'Invalid OTP code'], 400);
        }

        if (Carbon::now()->isAfter($user->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'OTP code has expired'], 400);
        }

        // Generate a verification token for password change step
        $verificationToken = bin2hex(random_bytes(32));
        
        // Store token temporarily (use otp_code field to store it)
        $user->update([
            'otp_verified' => true,
            'otp_code' => $verificationToken, // Repurpose for verification token
            'otp_expires_at' => Carbon::now()->addMinutes(15), // 15 min for password change
            'email_verified_at' => $user->email_verified_at ?? Carbon::now(),
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'OTP verified successfully',
            'token' => $verificationToken, // Return token for password change step
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'role' => $user->role,
            ]
        ]);
    }

    /**
     * Resend OTP to user email
     */
    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid email'], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // Generate new OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(30),
            'otp_verified' => false,
        ]);

        // Send OTP email
        try {
            Mail::send([], [], function ($message) use ($user, $otp) {
                $message->to($user->email)
                    ->subject('PinPointMe - New Verification Code')
                    ->html("
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                            <div style='background: linear-gradient(135deg, #1976D2, #0D47A1); padding: 30px; text-align: center;'>
                                <h1 style='color: white; margin: 0;'>PinPointMe</h1>
                            </div>
                            <div style='padding: 30px; background: #f5f5f5;'>
                                <h2 style='color: #333;'>New Verification Code</h2>
                                <p style='color: #666;'>Hi {$user->first_name}, here's your new verification code:</p>
                                <div style='background: white; padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0;'>
                                    <h1 style='color: #1976D2; letter-spacing: 8px; font-size: 36px; margin: 0;'>{$otp}</h1>
                                    <p style='color: #888; font-size: 12px; margin-top: 10px;'>Code expires in 30 minutes</p>
                                </div>
                            </div>
                        </div>
                    ");
            });
        } catch (\Exception $e) {
            \Log::error('Failed to resend OTP: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to send email'], 500);
        }

        return response()->json(['success' => true, 'message' => 'New OTP sent successfully']);
    }

    /**
     * Activate account and set new password
     */
    public function activateAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid input', 'errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // Verify the token
        if ($user->otp_code !== $request->token) {
            return response()->json(['success' => false, 'message' => 'Invalid verification token'], 400);
        }

        if (!$user->otp_verified) {
            return response()->json(['success' => false, 'message' => 'Email not verified'], 400);
        }

        if ($user->otp_expires_at && Carbon::now()->isAfter($user->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'Verification token expired. Please request a new OTP.'], 400);
        }

        // Activate account and set new password
        $user->update([
            'password' => Hash::make($request->password),
            'status' => 'active',
            'otp_code' => null,
            'otp_expires_at' => null,
            'must_change_password' => false,
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'Account activated successfully! You can now log in.'
        ]);
    }

    /**
     * Validate SDCA email format
     */
    public static function isValidSdcaEmail($email)
    {
        // In local/development environment, allow any email for testing
        if (app()->environment('local', 'development', 'testing')) {
            return true;
        }
        
        // Allow @sdca.edu.ph emails for production
        // Also allow common test emails for development
        $validDomains = ['sdca.edu.ph'];
        $testEmails = ['admin@example.com', 'admin1@example.com', 'test@example.com'];
        
        // Check if it's a test email (for development)
        if (in_array(strtolower($email), $testEmails)) {
            return true;
        }
        
        // Check domain
        $emailParts = explode('@', $email);
        if (count($emailParts) !== 2) {
            return false;
        }
        
        $domain = strtolower($emailParts[1]);
        
        return in_array($domain, $validDomains);
    }

    /**
     * Change the user's password.
     */
    // public function changePassword(ChangePasswordFormRequest $request)
    // {
    //     // Prefer using request->filled() over exists() for clarity
    //     $profileId = $request->filled('profile_id')
    //         ? $request->input('profile_id')
    //         : Auth::user()->profile->id;

    //     // Merge the resolved profile_id back into the request so DTO sees it
    //     $request->merge(['profile_id' => $profileId]);

    //     // Build the DTO from the request
    //     $changePasswordDTO = ChangePasswordDTO::fromArray($request->toArray());

    //     // Call service
    //     [
    //         'code'    => $code,
    //         'status'  => $status,
    //         'message' => $message
    //     ] = $this->manageAccount->changeUserProfilePassword($changePasswordDTO, $profileId);

    //     // Normalize error message for production
    //     $productionErrorMessage = ErrorHelper::productionErrorMessage($code, $message);

    //     // Handle error
    //     if ($status === Helper::ERROR) {
    //         return Inertia::render('Error', [
    //             'code'    => $code,
    //             'message' => $productionErrorMessage,
    //         ]);
    //     }

    //     // Success → redirect back with flash message
    //     return redirect()->back()->with($status, $message);
    // }

    /**
     * Reset the user's password.
     */
    // public function resetPassword(int $userId)
    // {
    //     [
    //         'code' => $code,
    //         'status'  => $status,
    //         'message' => $message
    //     ] = $this->manageAccount->resetPassword($userId);

    //     // Normalize error message for production
    //     $productionErrorMessage = ErrorHelper::productionErrorMessage($code, $message);

    //     // Handle error
    //     if ($status === Helper::ERROR) {
    //         return Inertia::render('Error', [
    //             'code'    => $code,
    //             'message' => $productionErrorMessage,
    //         ]);
    //     }

    //     // Success → redirect back with flash message
    //     return redirect()->back()->with($status, $message);
    // }

    /**
     * Set the user's active status.
     */
    // public function setUserStatus(int $userId)
    // {
    //     [
    //         'code' => $code,
    //         'status'  => $status,
    //         'message' => $message
    //     ] = $this->manageAccount->setUserActiveStatus($userId);

    //     // Normalize error message for production
    //     $productionErrorMessage = ErrorHelper::productionErrorMessage($code, $message);

    //     // Handle error
    //     if ($status === Helper::ERROR) {
    //         return Inertia::render('Error', [
    //             'code'    => $code,
    //             'message' => $productionErrorMessage,
    //         ]);
    //     }

    //     // Success → redirect back with flash message
    //     return redirect()->back()->with($status, $message);
    // }

    // public function changeProperty(int $propertyId)
    // {

    //     [
    //         'code' => $code,
    //         'status'  => $status,
    //         'message' => $message
    //     ] = $this->manageAccount->changeProperty($propertyId);

    //     // Normalize error message for production
    //     $productionErrorMessage = ErrorHelper::productionErrorMessage($code, $message);

    //     // Handle error
    //     if ($status === Helper::ERROR) {
    //         return Inertia::render('Error', [
    //             'code'    => $code,
    //             'message' => $productionErrorMessage,
    //         ]);
    //     }

    //     // Success → redirect back with flash message
    //     return redirect()->back()->with($status, $message);
    // }

    /**
     * Show user details
     */
    public function showUser(User $user)
    {
        return response()->json($user);
    }

    /**
     * API Login for mobile/user app
     */
    public function apiLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid input data',
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('email', 'password');
        $user = User::where('email', $credentials['email'])->first();
        
        if ($user && Hash::check($credentials['password'], $user->password)) {
            // Create Sanctum token
            $token = $user->createToken('api-token')->plainTextToken;
            
            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'token' => $token,
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name ?? '',
                'last_name' => $user->last_name ?? '',
                'role' => $user->role ?? 'student',
                'username' => $user->username ?? '',
                'data' => $user
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Invalid credentials'
        ], 401);
    }

    /**
     * API Register for mobile/user app
     */
    public function apiRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone_number,
            'role' => 'student',
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Registration successful',
            'token' => $token,
            'data' => $user
        ], 201);
    }

    /**
     * API Logout
     */
    public function apiLogout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Update user profile
     */
    public function updateUser(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|max:20',
            'emergency_contact_name' => 'sometimes|string|max:255',
            'emergency_contact_phone' => 'sometimes|string|max:20',
            'emergency_contact_relation' => 'sometimes|string|max:255',
            'blood_type' => 'sometimes|string|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'allergies' => 'sometimes|string',
            'medical_conditions' => 'sometimes|string',
            'current_password' => 'sometimes|string|min:8',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // If password is being changed, verify current password
        if (isset($data['password']) && isset($data['current_password'])) {
            if (!Hash::check($data['current_password'], $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect'
                ], 422);
            }
            $data['password'] = Hash::make($data['password']);
            unset($data['current_password']);
        }

        // Update user
        $user->update($data);

        return response()->json($user);
    }

    /**
     * Upload user profile picture
     */
    public function uploadProfilePicture(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Delete old profile picture if exists
        if ($user->profile_picture) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_picture);
        }

        // Store new profile picture
        $path = $request->file('profile_picture')->store('profile_pictures', 'public');
        $user->profile_picture = $path;
        $user->save();

        return response()->json([
            'message' => 'Profile picture uploaded successfully',
            'profile_picture' => $path,
            'profile_picture_url' => asset('storage/' . $path),
            'user' => $user
        ]);
    }

    /**
     * Delete user profile picture
     */
    public function deleteProfilePicture(User $user)
    {
        if ($user->profile_picture) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_picture);
            $user->profile_picture = null;
            $user->save();
        }

        return response()->json([
            'message' => 'Profile picture deleted successfully',
            'user' => $user
        ]);
    }

    /**
     * Send OTP for password change (forced password change flow)
     */
    public function sendPasswordChangeOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid email'], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store OTP with expiration (5 minutes)
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(5),
            'otp_verified' => false,
        ]);

        // Send OTP via email
        try {
            Mail::send([], [], function ($message) use ($user, $otp) {
                $message->to($user->email)
                    ->subject('PinPointMe - Password Change Verification')
                    ->html("
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                            <div style='background: linear-gradient(135deg, #1976D2, #0D47A1); color: white; padding: 30px; text-align: center;'>
                                <h1 style='margin: 0;'>PinPointMe</h1>
                                <p style='margin: 5px 0 0 0; opacity: 0.9;'>Emergency Rescue System</p>
                            </div>
                            <div style='padding: 30px; background: #f9f9f9;'>
                                <h2 style='color: #333; margin-top: 0;'>Password Change Required</h2>
                                <p style='color: #666;'>Hello {$user->first_name},</p>
                                <p style='color: #666;'>You need to change your temporary password. Use this verification code:</p>
                                <div style='background: white; border: 2px solid #1976D2; border-radius: 10px; padding: 25px; text-align: center; margin: 25px 0;'>
                                    <h1 style='color: #1976D2; letter-spacing: 10px; font-size: 40px; margin: 0; font-family: monospace;'>{$otp}</h1>
                                </div>
                                <p style='color: #999; font-size: 13px; text-align: center;'>This code will expire in <strong>5 minutes</strong></p>
                                <div style='background: #FFF3E0; border-left: 4px solid #FF9800; padding: 15px; margin-top: 20px;'>
                                    <p style='color: #E65100; margin: 0; font-size: 13px;'>
                                        <strong>Security Notice:</strong> If you didn't request this password change, please contact your administrator immediately.
                                    </p>
                                </div>
                            </div>
                            <div style='padding: 15px; background: #0D47A1; text-align: center; font-size: 12px; color: rgba(255,255,255,0.8);'>
                                &copy; " . date('Y') . " PinPointMe - SDCA Emergency Rescue System
                            </div>
                        </div>
                    ");
            });

            return response()->json([
                'success' => true, 
                'message' => 'Verification code sent to your email',
                'expires_in' => 300
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send password change OTP: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to send verification email'], 500);
        }
    }

    /**
     * Verify OTP for password change
     */
    public function verifyPasswordChangeOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid input'], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // Log for debugging
        \Log::info('OTP Verification attempt', [
            'email' => $request->email,
            'submitted_otp' => $request->otp,
            'stored_otp' => $user->otp_code,
            'expires_at' => $user->otp_expires_at,
        ]);

        // Check if OTP exists
        if (!$user->otp_code) {
            return response()->json(['success' => false, 'message' => 'No verification code found. Please request a new one.'], 400);
        }

        // Check if OTP matches (trim whitespace just in case)
        $submittedOtp = trim($request->otp);
        $storedOtp = trim($user->otp_code);
        
        if ($storedOtp !== $submittedOtp) {
            return response()->json(['success' => false, 'message' => 'Invalid verification code'], 400);
        }

        // Check expiration
        if ($user->otp_expires_at && Carbon::now()->isAfter($user->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'Verification code has expired'], 400);
        }

        // Generate a verification token for password change step
        $verificationToken = bin2hex(random_bytes(32));
        
        $user->update([
            'otp_verified' => true,
            'otp_code' => $verificationToken,
            'otp_expires_at' => Carbon::now()->addMinutes(15),
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'Verification successful',
            'token' => $verificationToken,
        ]);
    }

    /**
     * Complete password change after OTP verification
     */
    public function completePasswordChange(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Invalid input', 
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // Verify the token
        if ($user->otp_code !== $request->token) {
            return response()->json(['success' => false, 'message' => 'Invalid verification token'], 400);
        }

        if (!$user->otp_verified) {
            return response()->json(['success' => false, 'message' => 'Email not verified'], 400);
        }

        if ($user->otp_expires_at && Carbon::now()->isAfter($user->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'Token expired. Please request a new verification code.'], 400);
        }

        // Update password and mark as changed
        $user->update([
            'password' => Hash::make($request->password),
            'force_password_change' => false,
            'password_changed_at' => Carbon::now(),
            'otp_code' => null,
            'otp_expires_at' => null,
            'otp_verified' => false,
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'Password changed successfully!'
        ]);
    }

    /**
     * Show forced password change page
     */
    public function showChangePassword()
    {
        $user = Auth::user();
        
        return Inertia::render('ChangePassword', [
            'email' => $user->email,
            'role' => $user->role,
        ]);
    }




}
