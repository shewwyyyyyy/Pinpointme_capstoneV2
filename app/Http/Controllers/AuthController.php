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
use Illuminate\Support\Facades\Schema;
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
                
                // Only send new OTP if there's no valid existing OTP
                $hasValidOtp = $user->otp_code && 
                               strlen($user->otp_code) === 6 && 
                               $user->otp_expires_at && 
                               Carbon::now()->isBefore($user->otp_expires_at);
                
                if (!$hasValidOtp) {
                    // Generate and send new OTP
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
        // For rescuers, set status to 'available'; for others, set to 'active'
        $newStatus = $user->role === 'rescuer' ? 'available' : 'active';
        
        $user->update([
            'password' => Hash::make($request->password),
            'status' => $newStatus,
            'otp_code' => null,
            'otp_expires_at' => null,
            'otp_verified' => true,
            'must_change_password' => false,
            'force_password_change' => false, // Prevent double password change
            'password_changed_at' => Carbon::now(),
        ]);
        
        // Send congratulatory email
        try {
            Mail::send([], [], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Welcome to PinPointMe - Account Activated!')
                    ->html("
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                            <div style='background: linear-gradient(135deg, #1976D2, #0D47A1); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                                <h1 style='color: white; margin: 0;'>🎉 Welcome to PinPointMe!</h1>
                            </div>
                            <div style='background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;'>
                                <h2 style='color: #2E7D32;'>Congratulations, {$user->first_name}!</h2>
                                <p style='color: #333; line-height: 1.6;'>
                                    Your account has been successfully activated. You're now ready to use the PinPointMe Emergency Response System.
                                </p>
                                <div style='background: #e8f5e9; border-left: 4px solid #4caf50; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                                    <p style='margin: 0; color: #2E7D32;'>
                                        <strong>✓ Account Status:</strong> " . ucfirst($newStatus) . "<br>
                                        <strong>✓ Email:</strong> {$user->email}<br>
                                        <strong>✓ Role:</strong> " . ucfirst($user->role) . "
                                    </p>
                                </div>
                                <p style='color: #333; line-height: 1.6;'>
                                    You can now log in to the system and access all the features available to your role.
                                </p>
                                <div style='text-align: center; margin-top: 30px;'>
                                    <a href='https://pinpointme.app/login' style='background: linear-gradient(135deg, #1976D2, #0D47A1); color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; font-weight: bold;'>
                                        Login to PinPointMe
                                    </a>
                                </div>
                                <p style='color: #666; font-size: 12px; margin-top: 30px; text-align: center;'>
                                    If you have any questions, please contact support at support@sdca.edu.ph
                                </p>
                            </div>
                        </div>
                    ");
            });
        } catch (\Exception $e) {
            // Log email error but don't fail the activation
            \Log::error('Failed to send activation email: ' . $e->getMessage());
        }

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
            'phone' => 'sometimes|string|max:13', // Allow +639XXXXXXXXX format
            'phone_number' => 'sometimes|string|max:13', // Allow +639XXXXXXXXX format
            'id_number' => 'sometimes|string|digits:9',
            'emergency_contact_name' => 'sometimes|string|max:255',
            'emergency_contact_phone' => 'sometimes|string|max:13', // Allow +639XXXXXXXXX format
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

        // Validate personal phone number if provided
        if (isset($data['phone_number']) || isset($data['phone'])) {
            $phoneValue = $data['phone_number'] ?? $data['phone'];
            $cleanedPhone = preg_replace('/[\s\-\(\)]/', '', $phoneValue);
            
            // Valid mobile formats: 09XXXXXXXXX (11 digits total)
            if (!preg_match('/^09[0-9]{9}$/', $cleanedPhone)) {
                return response()->json([
                    'message' => 'Please enter a valid mobile number (e.g., 09171234567)',
                    'errors' => ['phone_number' => ['Invalid mobile number format. Use 09XXXXXXXXX (11 digits)']]
                ], 422);
            }
        }

        // Validate phone number if provided
        if (isset($data['phone_number']) || isset($data['phone'])) {
            $phoneValue = $data['phone_number'] ?? $data['phone'];
            $cleanedPhone = preg_replace('/[\s\-\(\)]/', '', $phoneValue);
            
            // Valid mobile formats: 09XXXXXXXXX (11 digits total)
            if (!preg_match('/^09[0-9]{9}$/', $cleanedPhone)) {
                return response()->json([
                    'message' => 'Please enter a valid mobile number (e.g., 09171234567)',
                    'errors' => ['phone_number' => ['Invalid mobile number format. Use 09XXXXXXXXX (11 digits)']]
                ], 422);
            }
        }

        // Handle id_number - validate and determine role
        if (isset($data['id_number'])) {
            $idNumber = $data['id_number'];
            
            // Ensure it's exactly 9 digits
            if (!preg_match('/^\d{9}$/', $idNumber)) {
                return response()->json([
                    'message' => 'ID Number must be exactly 9 digits (numbers only)',
                    'errors' => ['id_number' => ['ID Number must be exactly 9 digits']]
                ], 422);
            }
            
            // Determine role based on ID number pattern
            $firstDigit = $idNumber[0];
            
            // If starts with digit 2, it's a student
            if ($firstDigit === '2') {
                $data['student_id'] = $idNumber;
                // Update role to student if not already a higher role
                if (!in_array($user->role, ['admin', 'rescuer'])) {
                    $data['role'] = 'student';
                }
            } else {
                // Otherwise (starts with 1,3,4,5,6,7,8,9), it's faculty
                $data['faculty_id'] = $idNumber;
                // Update role to faculty if not already a higher role
                if (!in_array($user->role, ['admin', 'rescuer'])) {
                    $data['role'] = 'faculty';
                }
            }
            
            unset($data['id_number']);
        }

        // Validate emergency contact phone number if provided
        if (isset($data['emergency_contact_phone'])) {
            $phoneValue = $data['emergency_contact_phone'];
            $cleanedPhone = preg_replace('/[\\s\\-\\(\\)]/', '', $phoneValue);
            
            // Valid mobile formats: 09XXXXXXXXX (11 digits total)
            if (!preg_match('/^09[0-9]{9}$/', $cleanedPhone)) {
                return response()->json([
                    'message' => 'Please enter a valid mobile number for emergency contact (e.g., 09171234567)',
                    'errors' => ['emergency_contact_phone' => ['Invalid mobile number format. Use 09XXXXXXXXX (11 digits)']]
                ], 422);
            }
        }

        // Map phone_number to phone if provided
        if (isset($data['phone_number'])) {
            $data['phone'] = $data['phone_number'];
            unset($data['phone_number']);
        }

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
        $updateData = [
            'password' => Hash::make($request->password),
            'force_password_change' => false,
            'password_changed_at' => Carbon::now(),
            'otp_code' => null,
            'otp_expires_at' => null,
            'otp_verified' => false,
        ];
        
        // For rescuers, set status to 'available' after first password change
        if ($user->role === 'rescuer' && ($user->status === 'pending' || $user->status === 'inactive' || !$user->password_changed_at)) {
            $updateData['status'] = 'available';
        }
        
        $user->update($updateData);
        
        // Send password change confirmation email
        try {
            $newStatus = $updateData['status'] ?? $user->status;
            Mail::send([], [], function ($message) use ($user, $newStatus) {
                $message->to($user->email)
                    ->subject('Password Changed Successfully - PinPointMe')
                    ->html("
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                            <div style='background: linear-gradient(135deg, #1976D2, #0D47A1); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                                <h1 style='color: white; margin: 0;'>🔐 Password Changed</h1>
                            </div>
                            <div style='background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;'>
                                <h2 style='color: #1976D2;'>Hello, {$user->first_name}!</h2>
                                <p style='color: #333; line-height: 1.6;'>
                                    Your password has been successfully changed. You can now use your new password to log in to PinPointMe.
                                </p>
                                <div style='background: #e3f2fd; border-left: 4px solid #1976D2; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                                    <p style='margin: 0; color: #1565C0;'>
                                        <strong>Account Details:</strong><br>
                                        Email: {$user->email}<br>
                                        Role: " . ucfirst($user->role) . "<br>
                                        Status: " . ucfirst($newStatus) . "
                                    </p>
                                </div>
                                <p style='color: #333; line-height: 1.6;'>
                                    If you did not make this change, please contact support immediately.
                                </p>
                                <div style='text-align: center; margin-top: 30px;'>
                                    <a href='https://pinpointme.app/login' style='background: linear-gradient(135deg, #1976D2, #0D47A1); color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; font-weight: bold;'>
                                        Login Now
                                    </a>
                                </div>
                                <p style='color: #666; font-size: 12px; margin-top: 30px; text-align: center;'>
                                    This is an automated message from PinPointMe Emergency Response System
                                </p>
                            </div>
                        </div>
                    ");
            });
        } catch (\Exception $e) {
            // Log email error but don't fail the password change
            \Log::error('Failed to send password change email: ' . $e->getMessage());
        }

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

    /**
     * Handle forgot password request - sends password reset link
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please enter a valid email address',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        // For security, we return success even if user doesn't exist
        // to prevent email enumeration
        if (!$user) {
            return response()->json([
                'status' => 'success',
                'message' => 'If an account exists with this email, a password reset link has been sent.'
            ]);
        }

        // Generate a password reset token (6-digit OTP)
        $resetToken = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store token with expiration (30 minutes for forgot password)
        $user->update([
            'otp_code' => $resetToken,
            'otp_expires_at' => Carbon::now()->addMinutes(30),
            'otp_verified' => false,
        ]);

        // Generate reset URL
        $resetUrl = url('/reset-password?email=' . urlencode($user->email) . '&token=' . $resetToken);

        // Send password reset email
        try {
            Mail::send([], [], function ($message) use ($user, $resetToken, $resetUrl) {
                $message->to($user->email)
                    ->subject('PinPointMe - Reset Your Password')
                    ->html("
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                            <div style='background: linear-gradient(135deg, #3674B5, #1E4A7A); color: white; padding: 30px; text-align: center;'>
                                <h1 style='margin: 0;'>PinPointMe</h1>
                                <p style='margin: 5px 0 0 0; opacity: 0.9;'>COMING YOUR WAY.</p>
                            </div>
                            <div style='padding: 30px; background: #f9f9f9;'>
                                <h2 style='color: #333; margin-top: 0;'>Password Reset Request</h2>
                                <p style='color: #666;'>Hello {$user->first_name},</p>
                                <p style='color: #666;'>We received a request to reset your password. Use this verification code:</p>
                                <div style='background: white; border: 2px solid #3674B5; border-radius: 10px; padding: 25px; text-align: center; margin: 25px 0;'>
                                    <h1 style='color: #3674B5; letter-spacing: 10px; font-size: 40px; margin: 0; font-family: monospace;'>{$resetToken}</h1>
                                </div>
                                <p style='color: #999; font-size: 13px; text-align: center;'>This code will expire in <strong>30 minutes</strong></p>
                                <p style='color: #666; text-align: center; margin-top: 20px;'>Or click the button below to reset your password:</p>
                                <div style='text-align: center; margin: 25px 0;'>
                                    <a href='{$resetUrl}' style='display: inline-block; background: #3674B5; color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold;'>Reset Password</a>
                                </div>
                                <div style='background: #FFF3E0; border-left: 4px solid #FF9800; padding: 15px; margin-top: 20px;'>
                                    <p style='color: #E65100; margin: 0; font-size: 13px;'>
                                        <strong>Security Notice:</strong> If you didn't request this password reset, please ignore this email or contact support if you have concerns.
                                    </p>
                                </div>
                            </div>
                            <div style='padding: 15px; background: #1E4A7A; text-align: center; font-size: 12px; color: rgba(255,255,255,0.8);'>
                                &copy; " . date('Y') . " PinPointMe - Emergency Rescue System
                            </div>
                        </div>
                    ");
            });

            return response()->json([
                'status' => 'success', 
                'message' => 'If an account exists with this email, a password reset link has been sent.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send password reset email: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send reset email. Please try again later.'
            ], 500);
        }
    }

    /**
     * Send OTP for new user registration
     */
    public function registerSendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid email'], 422);
        }

        $email = trim($request->email);
        
        // Validate SDCA email
        if (!self::isValidSdcaEmail($email)) {
            return response()->json([
                'success' => false, 
                'message' => 'Only SDCA email addresses (@sdca.edu.ph) are allowed for registration'
            ], 422);
        }

        // Check if user already exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            return response()->json([
                'success' => false, 
                'message' => 'An account with this email already exists'
            ], 422);
        }

        // Generate 6-digit OTP and temporary password
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $tempPassword = 'Temp' . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT) . '!';
        
        // Create pending user account
        $userData = [
            'email' => $email,
            'password' => Hash::make($tempPassword),
            'role' => 'student',
            'status' => 'pending',
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(15),
            'otp_verified' => false,
            'force_password_change' => true,
        ];
        
        // Add must_update_profile field if it exists in the database
        if (\Schema::hasColumn('users', 'must_update_profile')) {
            $userData['must_update_profile'] = true;
        }
        
        $user = User::create($userData);

        // Send OTP and temporary password via email
        try {
            Mail::send([], [], function ($message) use ($user, $otp, $tempPassword) {
                $message->to($user->email)
                    ->subject('PinPointMe - Account Registration')
                    ->html("
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                            <div style='background: linear-gradient(135deg, #3674B5 0%, #2D5F96 100%); padding: 30px; text-align: center;'>
                                <h1 style='color: white; margin: 0; font-size: 28px;'>Welcome to PinPointMe!</h1>
                                <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 16px;'>Your Emergency Rescue System</p>
                            </div>
                            <div style='padding: 30px; background-color: #f8f9fa;'>
                                <h2 style='color: #333; margin-bottom: 20px;'>Registration Details</h2>
                                <p style='color: #666; font-size: 16px; line-height: 1.6;'>Thank you for registering with PinPointMe. Here are your registration details:</p>
                                
                                <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #3674B5;'>
                                    <h3 style='color: #3674B5; margin-top: 0;'>Verification Code (OTP):</h3>
                                    <p style='font-size: 24px; font-weight: bold; color: #333; margin: 10px 0;'>{$otp}</p>
                                </div>
                                
                                <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #e74c3c;'>
                                    <h3 style='color: #e74c3c; margin-top: 0;'>Temporary Password:</h3>
                                    <p style='font-size: 18px; font-weight: bold; color: #333; margin: 10px 0; font-family: monospace; background: #f5f5f5; padding: 10px; border-radius: 4px;'>{$tempPassword}</p>
                                </div>
                                
                                <div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border: 1px solid #ffeaa7;'>
                                    <p style='color: #856404; margin: 0; font-size: 14px;'>
                                        <strong>Important:</strong> You must change this temporary password immediately after your first login for security reasons.
                                    </p>
                                </div>
                                
                                <h3 style='color: #333; margin-top: 30px;'>Next Steps:</h3>
                                <ol style='color: #666; line-height: 1.8;'>
                                    <li>Enter the verification code in the registration form</li>
                                    <li>Set your new secure password</li>
                                    <li>Log in with your new password</li>
                                    <li>Complete your profile information</li>
                                </ol>
                                
                                <p style='color: #666; font-size: 14px; margin-top: 30px;'>
                                    This verification code will expire in 15 minutes. If you need a new code, please request one from the registration form.
                                </p>
                            </div>
                            <div style='background: #333; padding: 20px; text-align: center;'>
                                <p style='color: #ccc; margin: 0; font-size: 14px;'>PinPointMe Emergency Rescue System</p>
                                <p style='color: #999; margin: 5px 0 0 0; font-size: 12px;'>This is an automated message. Please do not reply to this email.</p>
                            </div>
                        </div>
                    ");
            });

            return response()->json([
                'success' => true, 
                'message' => 'Registration code and temporary password sent to your email',
                'expires_in' => 900 // 15 minutes in seconds
            ]);
        } catch (\Exception $e) {
            // Delete the user if email failed to send
            $user->delete();
            \Log::error('Failed to send registration email: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to send registration email'], 500);
        }
    }

    /**
     * Verify OTP for registration
     */
    public function registerVerifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid input'], 422);
        }

        $user = User::where('email', $request->email)->where('status', 'pending')->first();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Registration not found or already completed'], 404);
        }

        // Check if OTP matches and hasn't expired
        if ($user->otp_code !== $request->otp) {
            return response()->json(['success' => false, 'message' => 'Invalid verification code'], 400);
        }

        if (Carbon::now()->isAfter($user->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'Verification code has expired'], 400);
        }

        // Generate a registration token for password change step
        $registrationToken = bin2hex(random_bytes(32));
        
        // Store token temporarily
        $user->update([
            'otp_verified' => true,
            'otp_code' => $registrationToken, // Repurpose for registration token
            'otp_expires_at' => Carbon::now()->addMinutes(30), // 30 min for password change
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'Verification code confirmed successfully',
            'token' => $registrationToken,
        ]);
    }

    /**
     * Complete registration with new password
     */
    public function registerComplete(Request $request)
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

        $user = User::where('email', $request->email)->where('status', 'pending')->first();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Registration not found or already completed'], 404);
        }

        // Verify the token
        if ($user->otp_code !== $request->token) {
            return response()->json(['success' => false, 'message' => 'Invalid registration token'], 400);
        }

        if (!$user->otp_verified) {
            return response()->json(['success' => false, 'message' => 'Email not verified'], 400);
        }

        if ($user->otp_expires_at && Carbon::now()->isAfter($user->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'Registration token expired. Please start registration again.'], 400);
        }

        // Complete registration
        $updateData = [
            'password' => Hash::make($request->password),
            'status' => 'active',
            'otp_code' => null,
            'otp_expires_at' => null,
            'otp_verified' => true,
            'force_password_change' => false,
            'password_changed_at' => Carbon::now(),
            'email_verified_at' => Carbon::now(),
        ];
        
        // Keep must_update_profile => true to force profile completion if column exists
        if (\Schema::hasColumn('users', 'must_update_profile')) {
            $updateData['must_update_profile'] = true;
        }
        
        $user->update($updateData);
        
        // Send welcome email
        try {
            Mail::send([], [], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Welcome to PinPointMe!')
                    ->html("
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                            <div style='background: linear-gradient(135deg, #3674B5 0%, #2D5F96 100%); padding: 30px; text-align: center;'>
                                <h1 style='color: white; margin: 0; font-size: 28px;'>Welcome to PinPointMe!</h1>
                                <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 16px;'>Your account is ready!</p>
                            </div>
                            <div style='padding: 30px; background-color: #f8f9fa;'>
                                <h2 style='color: #333; margin-bottom: 20px;'>Account Activated Successfully</h2>
                                <p style='color: #666; font-size: 16px; line-height: 1.6;'>Congratulations! Your PinPointMe account has been successfully created and activated.</p>
                                
                                <div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745;'>
                                    <h3 style='color: #155724; margin-top: 0;'>What's Next?</h3>
                                    <ul style='color: #155724; margin: 10px 0;'>
                                        <li>Log in to your account</li>
                                        <li>Complete your profile information</li>
                                        <li>Start using the emergency rescue system</li>
                                    </ul>
                                </div>
                                
                                <p style='color: #666; font-size: 16px; line-height: 1.6;'>Please note that you'll need to complete your profile information before you can submit emergency reports.</p>
                                
                                <div style='text-align: center; margin: 30px 0;'>
                                    <a href='" . url('/login') . "' style='background: #3674B5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Login Now</a>
                                </div>
                            </div>
                            <div style='background: #333; padding: 20px; text-align: center;'>
                                <p style='color: #ccc; margin: 0; font-size: 14px;'>PinPointMe Emergency Rescue System</p>
                                <p style='color: #999; margin: 5px 0 0 0; font-size: 12px;'>This is an automated message. Please do not reply to this email.</p>
                            </div>
                        </div>
                    ");
            });
        } catch (\Exception $e) {
            // Log email error but don't fail the registration
            \Log::error('Failed to send welcome email: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true, 
            'message' => 'Registration completed successfully! You can now log in.'
        ]);
    }

    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle()
    {
        return \Laravel\Socialite\Facades\Socialite::driver('google')
            ->with(['hd' => 'sdca.edu.ph']) // Restrict to SDCA domain at OAuth level
            ->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = \Laravel\Socialite\Facades\Socialite::driver('google')->user();
            
            // Verify SDCA domain
            $email = strtolower($googleUser->getEmail());
            $domain = substr($email, strpos($email, '@') + 1);
            
            if ($domain !== 'sdca.edu.ph') {
                return redirect('/login')->withErrors([
                    'google' => 'Only SDCA email addresses (@sdca.edu.ph) are allowed to sign in with Google.'
                ]);
            }
            
            // Check if user already exists
            $user = User::where('email', $email)->first();
            
            if ($user) {
                // Existing user - update Google OAuth data and login
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'google_token' => $googleUser->token,
                    'profile_picture' => $googleUser->getAvatar() ?? $user->profile_picture,
                    'last_login_at' => Carbon::now(),
                ]);
                
                Auth::login($user);
                $request->session()->regenerate();
                
                // Check if user needs to complete profile
                if ($user->must_update_profile) {
                    return redirect('/user/profile')->with('message', 'Please complete your profile information.');
                }
                
                // Redirect based on role
                if ($user->isAdmin == 1 || $user->isAdmin === true || $user->role === 'admin') {
                    return redirect()->intended('/admin/dashboard');
                } elseif ($user->role === 'rescuer') {
                    return redirect()->intended('/rescuer/dashboard');
                } else {
                    return redirect()->intended('/user/scanner');
                }
            }
            
            // New user - store Google data in session and redirect to complete registration
            $request->session()->put('google_user', [
                'google_id' => $googleUser->getId(),
                'email' => $email,
                'first_name' => $googleUser->offsetGet('given_name') ?? $googleUser->getName(),
                'last_name' => $googleUser->offsetGet('family_name') ?? '',
                'profile_picture' => $googleUser->getAvatar(),
                'google_token' => $googleUser->token,
            ]);
            
            return redirect('/auth/google/complete');
            
        } catch (\Exception $e) {
            \Log::error('Google OAuth error: ' . $e->getMessage());
            return redirect('/login')->withErrors([
                'google' => 'Failed to authenticate with Google. Please try again.'
            ]);
        }
    }

    /**
     * Show Google OAuth registration completion form
     */
    public function showGoogleComplete(Request $request)
    {
        $googleUser = $request->session()->get('google_user');
        
        if (!$googleUser) {
            return redirect('/login')->withErrors([
                'google' => 'Session expired. Please try signing in with Google again.'
            ]);
        }
        
        return Inertia::render('User/GoogleComplete', [
            'googleUser' => [
                'email' => $googleUser['email'],
                'first_name' => $googleUser['first_name'],
                'last_name' => $googleUser['last_name'],
                'profile_picture' => $googleUser['profile_picture'],
            ]
        ]);
    }

    /**
     * Complete Google OAuth registration with additional user data
     */
    public function completeGoogleRegistration(Request $request)
    {
        $googleUser = $request->session()->get('google_user');
        
        if (!$googleUser) {
            return response()->json([
                'success' => false,
                'message' => 'Session expired. Please try signing in with Google again.'
            ], 400);
        }
        
        // Validate the additional fields
        $validator = Validator::make($request->all(), [
            'id_number' => [
                'required',
                'string',
                'digits:9',
                'regex:/^[0-9]{9}$/',
            ],
            'phone_number' => [
                'required',
                'string',
                'regex:/^(09|\+639|639)[0-9]{9}$/',
                function ($attribute, $value, $fail) {
                    // Normalize the phone number
                    $normalized = preg_replace('/[^0-9]/', '', $value);
                    if (str_starts_with($normalized, '63')) {
                        $normalized = '0' . substr($normalized, 2);
                    }
                    if (str_starts_with($normalized, '9') && strlen($normalized) === 10) {
                        $normalized = '0' . $normalized;
                    }
                    
                    // Valid Philippine mobile prefixes
                    $validPrefixes = [
                        // Globe/TM
                        '0905', '0906', '0915', '0916', '0917', '0926', '0927', '0935', '0936', '0937', 
                        '0945', '0953', '0954', '0955', '0956', '0965', '0966', '0967', '0975', '0976', 
                        '0977', '0978', '0979', '0995', '0996', '0997',
                        // Smart/TNT/Sun
                        '0908', '0909', '0910', '0911', '0912', '0913', '0914', '0918', '0919', '0920', 
                        '0921', '0928', '0929', '0930', '0938', '0939', '0940', '0946', '0947', '0948', 
                        '0949', '0950', '0951', '0961', '0963', '0968', '0969', '0970', '0981', '0989', 
                        '0992', '0998', '0999',
                        // DITO
                        '0895', '0896', '0897', '0898', '0991', '0992', '0993', '0994',
                    ];
                    
                    $prefix = substr($normalized, 0, 4);
                    if (!in_array($prefix, $validPrefixes)) {
                        $fail('Please enter a valid Philippine mobile number with a recognized network prefix.');
                    }
                },
            ],
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => [
                'nullable',
                'string',
                'regex:/^(09|\+639|639)[0-9]{9}$/',
            ],
            'emergency_contact_relationship' => 'nullable|string|max:100',
        ], [
            'id_number.required' => 'ID number is required.',
            'id_number.digits' => 'ID number must be exactly 9 digits.',
            'id_number.regex' => 'ID number must contain only numbers.',
            'phone_number.required' => 'Phone number is required.',
            'phone_number.regex' => 'Please enter a valid Philippine mobile number (e.g., 09171234567).',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Determine role based on ID number first digit
        $idNumber = $request->id_number;
        $firstDigit = substr($idNumber, 0, 1);
        $role = ($firstDigit === '2') ? 'student' : 'faculty';
        
        // Create the user
        $userData = [
            'email' => $googleUser['email'],
            'first_name' => $googleUser['first_name'],
            'last_name' => $googleUser['last_name'],
            'profile_picture' => $googleUser['profile_picture'],
            'google_id' => $googleUser['google_id'],
            'google_token' => $googleUser['google_token'],
            'role' => $role,
            'contact_number' => $request->phone_number,
            'password' => Hash::make(\Illuminate\Support\Str::random(32)), // Random password since they use Google
            'status' => 'active',
            'otp_verified' => true,
            'email_verified_at' => Carbon::now(),
            'must_update_profile' => false,
        ];
        
        // Set appropriate ID field based on role
        if ($role === 'student') {
            $userData['student_id'] = $idNumber;
        } else {
            $userData['faculty_id'] = $idNumber;
        }
        
        // Add emergency contact if provided
        if ($request->emergency_contact_name) {
            $userData['emergency_contact_name'] = $request->emergency_contact_name;
        }
        if ($request->emergency_contact_phone) {
            $userData['emergency_contact_phone'] = $request->emergency_contact_phone;
        }
        if ($request->emergency_contact_relationship) {
            $userData['emergency_contact_relationship'] = $request->emergency_contact_relationship;
        }
        
        try {
            $user = User::create($userData);
            
            // Clear the Google session data
            $request->session()->forget('google_user');
            
            // Login the user
            Auth::login($user);
            $request->session()->regenerate();
            
            // Send welcome email
            try {
                Mail::send([], [], function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('Welcome to PinPointMe!')
                        ->html("
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                                <div style='background: linear-gradient(135deg, #3674B5 0%, #2D5F96 100%); padding: 30px; text-align: center;'>
                                    <h1 style='color: white; margin: 0; font-size: 28px;'>Welcome to PinPointMe!</h1>
                                    <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 16px;'>Your SDCA Google account is connected!</p>
                                </div>
                                <div style='padding: 30px; background-color: #f8f9fa;'>
                                    <h2 style='color: #333; margin-bottom: 20px;'>Account Created Successfully</h2>
                                    <p style='color: #666; font-size: 16px; line-height: 1.6;'>Your PinPointMe account has been created using your SDCA Google account.</p>
                                    
                                    <div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745;'>
                                        <h3 style='color: #155724; margin-top: 0;'>Account Details</h3>
                                        <ul style='color: #155724; margin: 10px 0;'>
                                            <li>Role: " . ucfirst($user->role) . "</li>
                                            <li>ID Number: " . ($user->student_id ?? $user->faculty_id) . "</li>
                                        </ul>
                                    </div>
                                    
                                    <p style='color: #666; font-size: 16px; line-height: 1.6;'>You can now use PinPointMe's emergency rescue system.</p>
                                </div>
                                <div style='background: #333; padding: 20px; text-align: center;'>
                                    <p style='color: #ccc; margin: 0; font-size: 14px;'>PinPointMe Emergency Rescue System</p>
                                </div>
                            </div>
                        ");
                });
            } catch (\Exception $e) {
                \Log::error('Failed to send Google registration welcome email: ' . $e->getMessage());
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Registration completed successfully!',
                'redirect' => '/user/scanner'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Google registration error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create account. Please try again.'
            ], 500);
        }
    }




}
