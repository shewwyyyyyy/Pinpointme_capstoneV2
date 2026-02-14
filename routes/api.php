<?php

use App\Http\Controllers\API\DepartmentApiController;
use App\Http\Controllers\API\LocationApiController;
use App\Http\Controllers\API\PropertyApiController;
use App\Http\Controllers\API\ScanAPIController;
use App\Http\Controllers\API\ScanHistoryApiController;
use App\Http\Controllers\API\ScanProfileApiController;
use App\Http\Controllers\API\EmployeeApiController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RescueRequestController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PushNotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ============================================================
// Authentication Routes (No CSRF)
// ============================================================
Route::post('/login', [AuthController::class, 'login']);

// OTP Verification Routes
Route::post('/auth/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']); // Alias for account verification page
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/activate-account', [AuthController::class, 'activateAccount']);

// Registration Routes
Route::post('/auth/register-send-otp', [AuthController::class, 'registerSendOtp']);
Route::post('/auth/register-verify-otp', [AuthController::class, 'registerVerifyOtp']);
Route::post('/auth/register-complete', [AuthController::class, 'registerComplete']);

// Password Change Routes  
Route::post('/auth/send-password-change-otp', [AuthController::class, 'sendPasswordChangeOtp']);
Route::post('/auth/verify-password-change-otp', [AuthController::class, 'verifyPasswordChangeOtp']);
Route::post('/auth/complete-password-change', [AuthController::class, 'completePasswordChange']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



// //profile api
Route::get('/profiles', [ScanProfileApiController::class, 'index']);
Route::post('/profiles/{unique_identifier}', [ScanProfileApiController::class, 'storeUniqueIdentifiers']);
Route::get('/departments', [DepartmentApiController::class, 'index']);
Route::get('/properties', [PropertyApiController::class, 'index']);
Route::get('/locations', [LocationApiController::class, 'index']);
Route::get('/scan-histories', [ScanHistoryApiController::class, 'index']);
Route::get('/employees', [EmployeeApiController::class, 'index']);

// ============================================================
// PinPointMe User App API Routes
// ============================================================

// User Authentication (Public)
Route::prefix('users')->group(function () {
    Route::post('/login', [AuthController::class, 'apiLogin']);
    Route::post('/register', [AuthController::class, 'apiRegister']);
});

// User active rescue and history - needs to be accessible for web app
Route::get('/users/{userId}/active-rescue', [RescueRequestController::class, 'userActiveRescue']);
Route::get('/users/{userId}/rescue-history', [RescueRequestController::class, 'userHistory']);
Route::get('/rescue-requests/user/{userId}', [RescueRequestController::class, 'userHistory']);

// Buildings API (Public - for QR scanning)
Route::get('/buildings', [BuildingController::class, 'index']);
Route::get('/buildings/{building}', [BuildingController::class, 'show']);

// Floors API
Route::get('/floors', [FloorController::class, 'index']);
Route::get('/floors/{floor}', [FloorController::class, 'show']);

// Rooms API
Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{room}', [RoomController::class, 'show']);
Route::put('/rooms/{room}/qr-code', [RoomController::class, 'updateQrCode']);
Route::post('/rooms/validate-qr', [RoomController::class, 'validateQrCode']);

// Rescue Requests API
Route::get('/rescue-requests', [RescueRequestController::class, 'index']);
Route::post('/rescue-requests', [RescueRequestController::class, 'store']);
Route::get('/rescue-requests/{rescueRequest}', [RescueRequestController::class, 'show']);
Route::put('/rescue-requests/{rescueRequest}', [RescueRequestController::class, 'update']);
Route::get('/rescue-requests/code/{code}', [RescueRequestController::class, 'showByCode']);
Route::patch('/rescue-requests/code/{code}/status', [RescueRequestController::class, 'updateStatus']);
Route::post('/rescue-requests/{rescueRequest}/mark-safe', [RescueRequestController::class, 'markSafe']);
Route::post('/rescue-requests/{rescueRequest}/translate', [RescueRequestController::class, 'translateRequest']);
Route::get('/rescue-requests/rescuer/{rescuer}', [RescueRequestController::class, 'rescuerFeed']);
Route::get('/users/{user}/rescue-history', [RescueRequestController::class, 'userHistory']);
Route::get('/location-details/{buildingId}/{floorId}/{roomId}', [RescueRequestController::class, 'getLocationDetails']);

// Admin force-alert: marks a rescue request so rescuers get unstoppable ringtone
Route::post('/rescue-requests/{rescueRequest}/force-alert', [RescueRequestController::class, 'forceAlert']);

// Push Notification Routes
Route::get('/push/vapid-public-key', [PushNotificationController::class, 'vapidPublicKey']);
Route::post('/push/subscribe', [PushNotificationController::class, 'subscribe'])->middleware('web');
Route::post('/push/unsubscribe', [PushNotificationController::class, 'unsubscribe'])->middleware('web');
Route::get('/push/test', [PushNotificationController::class, 'testNotification'])->middleware('web');
Route::get('/push/status', [PushNotificationController::class, 'status'])->middleware('web');
// Admin: get pending requests that have been waiting too long (>5 min)
Route::get('/rescue-requests/pending-too-long', [RescueRequestController::class, 'pendingTooLong']);

// User profile routes - accessible with session or token auth
Route::get('/users/{user}', [AuthController::class, 'showUser']);
Route::put('/users/{user}', [AuthController::class, 'updateUser']);
Route::post('/users/{user}/profile-picture', [AuthController::class, 'uploadProfilePicture']);
Route::delete('/users/{user}/profile-picture', [AuthController::class, 'deleteProfilePicture']);

// Conversations & Messaging API - accessible for web app (session auth)
Route::get('/conversations/admin', [ConversationController::class, 'adminIndex']); // Admin: all conversations
Route::get('/conversations', [ConversationController::class, 'index']);
Route::post('/conversations', [ConversationController::class, 'store']);
Route::get('/conversations/rescue/{rescueRequest}', [ConversationController::class, 'getOrCreateForRescue']);
Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
Route::post('/conversations/{conversation}/participants', [ConversationController::class, 'addParticipant']);
Route::post('/conversations/{conversation}/read', [ConversationController::class, 'markRead']);
Route::delete('/conversations/{conversation}', [ConversationController::class, 'destroy']);
Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index']);
Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store']);

// Messages
Route::get('/messages/{message}', [MessageController::class, 'show']);
Route::put('/messages/{message}', [MessageController::class, 'update']);
Route::delete('/messages/{message}', [MessageController::class, 'destroy']);

// Get rescuer user IDs for FCM notifications
Route::get('/rescuers/ids', [RescueRequestController::class, 'getRescuerIds']);

// Protected API Routes (requires Sanctum token authentication - for mobile apps)
Route::middleware('auth:sanctum')->group(function () {
    // User Management
    Route::post('/users/logout', [AuthController::class, 'apiLogout']);

    // Audit Trails
    Route::get('/audit-trails', [AuditTrailController::class, 'index']);
    Route::get('/audit-trails/{auditTrail}', [AuditTrailController::class, 'show']);
});
