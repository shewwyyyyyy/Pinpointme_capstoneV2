<?php

use App\Http\Controllers\AuthController;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

// Controllers for Building, Floor, Room, Rescue, Messaging
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RescueRequestController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\OpenAIController;
use App\Http\Controllers\PreventiveMeasureController;
use App\Http\Controllers\AdminController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Csrf;

use Illuminate\Support\Facades\Auth;

// ============================================================
// Authentication Routes
// ============================================================
Route::middleware(['guest'])->group(function () {
    // Global login page - works for user, rescuer, and admin
    Route::get('/login', function () {
        return Inertia::render('User/Login');
    })->name('login');
    
    // User-facing login page (same as global)
    Route::get('/user/login', function () {
        return Inertia::render('User/Login');
    })->name('user.login');
    
    // Account verification page for new users
    Route::get('/verify-account', function () {
        return Inertia::render('VerifyAccount', [
            'email' => request('email', ''),
        ]);
    })->name('verify.account');
});

Route::post('/login', [AuthController::class, 'login']);

// Forced password change page (requires auth but allows force_password_change users)
Route::middleware(['auth'])->group(function () {
    Route::get('/change-password', [AuthController::class, 'showChangePassword'])->name('change-password');
});

// ============================================================
// Public API Routes (No Auth Required)
// ============================================================
Route::prefix('api')->withoutMiddleware([Csrf::class])->group(function () {
    // Authentication - API login for mobile/SPA
    Route::post('/login', [AuthController::class, 'login']);
    
    // Password change OTP endpoints (for forced password change flow)
    Route::post('/auth/send-password-change-otp', [AuthController::class, 'sendPasswordChangeOtp']);
    Route::post('/auth/verify-password-change-otp', [AuthController::class, 'verifyPasswordChangeOtp']);
    Route::post('/auth/complete-password-change', [AuthController::class, 'completePasswordChange']);
    
    // Buildings with floors and rooms - for location scanner
    Route::get('/buildings', [BuildingController::class, 'apiIndex']);
    
    // Rescue requests - public endpoints
    Route::get('/rescue-requests', [RescueRequestController::class, 'index']);
    Route::post('/rescue-requests', [RescueRequestController::class, 'store']);
    Route::get('/rescue-requests/{id}', [RescueRequestController::class, 'show']);
    Route::put('/rescue-requests/{id}', [RescueRequestController::class, 'update']);
    Route::get('/rescue-requests/code/{code}', [RescueRequestController::class, 'showByCode']);
    Route::get('/rescue-requests/rescuer/{rescuerId}', [RescueRequestController::class, 'rescuerFeed']);
    Route::patch('/rescue-requests/code/{code}/status', [RescueRequestController::class, 'updateStatus']);
    
    // User specific endpoints
    Route::get('/users/{userId}/active-rescue', [RescueRequestController::class, 'userActiveRescue']);
    Route::get('/users/{userId}/rescue-history', [RescueRequestController::class, 'userHistory']);
    
    // Preventive Measures - public endpoints
    Route::get('/preventive-measures', [PreventiveMeasureController::class, 'index']);
    Route::get('/preventive-measures/categories', [PreventiveMeasureController::class, 'categories']);
    Route::get('/preventive-measures/{id}', [PreventiveMeasureController::class, 'show']);
});

// ============================================================
// OpenAI Processing Endpoints (Stateless, exclude CSRF for mobile/Ionic client)
// ============================================================
Route::prefix('openai')->withoutMiddleware([Csrf::class])->group(function () {
    Route::post('transcribe', [OpenAIController::class, 'transcribe']);
    Route::post('translate', [OpenAIController::class, 'translate']);
    Route::post('extract', [OpenAIController::class, 'extract']);
    Route::post('extract-full', [OpenAIController::class, 'extractFull']);
});

// ============================================================
// User-Facing Routes (Protected - require authentication)
// ============================================================
Route::middleware(['auth'])->prefix('user')->name('user.')->group(function () {
    // Location Scanner / Dashboard - Main user page
    Route::get('/dashboard', function () {
        return Inertia::render('User/LocationScanner');
    })->name('dashboard');

    Route::get('/scanner', function () {
        return Inertia::render('User/LocationScanner');
    })->name('scanner');

    // Help Coming / Rescue Status Tracking
    Route::get('/help-coming/{code?}', function ($code = null) {
        return Inertia::render('User/HelpComing', ['code' => $code]);
    })->name('help-coming');

    // Location History
    Route::get('/history', function () {
        return Inertia::render('User/LocationHistory');
    })->name('history');

    // Inbox / Messaging
    Route::get('/inbox', function () {
        return Inertia::render('User/Inbox');
    })->name('inbox');

    // Chat / Conversation - Unified Chat Component
    Route::get('/chat/{id}', function ($id) {
        return Inertia::render('Shared/Chat', [
            'conversationId' => $id,
            'userRole' => 'user'
        ]);
    })->name('chat');
    
    // Chat by Rescue Request ID
    Route::get('/rescue-chat/{rescueRequestId}', function ($rescueRequestId) {
        return Inertia::render('Shared/Chat', [
            'rescueRequestId' => $rescueRequestId,
            'userRole' => 'user'
        ]);
    })->name('rescue-chat');

    // Profile
    Route::get('/profile', function () {
        return Inertia::render('User/Profile');
    })->name('profile');
    
    // Preventive Measures - Educational content
    Route::get('/preventive-measures', [PreventiveMeasureController::class, 'userIndex'])->name('preventive-measures');
});

// ============================================================
// Rescuer-Facing Routes (Protected - require authentication)
// ============================================================
Route::middleware(['auth'])->prefix('rescuer')->name('rescuer.')->group(function () {
    // Rescuer Login - uses same global login page
    Route::get('/login', function () {
        return Inertia::render('User/Login');
    })->name('login');

    // Rescuer Dashboard - Main rescue feed
    Route::get('/dashboard', function () {
        return Inertia::render('Rescuer/Dashboard');
    })->name('dashboard');

    // Rescue Request Views
    Route::get('/pending/{id}', function ($id) {
        return Inertia::render('Rescuer/PendingRequest', ['requestId' => $id]);
    })->name('pending');

    Route::get('/in-progress/{code}', function ($code) {
        return Inertia::render('Rescuer/InProgressRequest', ['rescueCode' => $code]);
    })->name('in-progress');

    Route::get('/rescued/{id}', function ($id) {
        return Inertia::render('Rescuer/RescuedRequest', ['requestId' => $id]);
    })->name('rescued');

    // Active Rescue - Detail view for accepted rescue
    Route::get('/active/{id?}', function ($id = null) {
        return Inertia::render('Rescuer/ActiveRescue', ['rescueId' => $id]);
    })->name('active');

    // Map View - Floor plan with room highlighting
    Route::get('/map/{id?}', function ($id = null) {
        return Inertia::render('Rescuer/MapView', ['rescueId' => $id]);
    })->name('map');

    // Rescue History
    Route::get('/history', function () {
        return Inertia::render('Rescuer/History');
    })->name('history');

    // Messages / Chats Inbox
    Route::get('/chats', function () {
        return Inertia::render('Rescuer/Chats');
    })->name('chats');

    // Individual Chat - Unified Chat Component
    Route::get('/chat/{conversationId}', function ($conversationId) {
        return Inertia::render('Shared/Chat', [
            'conversationId' => $conversationId,
            'userRole' => 'rescuer'
        ]);
    })->name('chat');
    
    // Chat by Rescue Request ID
    Route::get('/rescue-chat/{rescueRequestId}', function ($rescueRequestId) {
        return Inertia::render('Shared/Chat', [
            'rescueRequestId' => $rescueRequestId,
            'userRole' => 'rescuer'
        ]);
    })->name('rescue-chat');

    // Rescuer Profile
    Route::get('/profile', function () {
        return Inertia::render('Rescuer/Profile');
    })->name('profile');
});


// ============================================================
// Admin Routes (Protected)
// ============================================================
// Admin Login - uses same global login page
Route::get('/admin/login', function () {
    return Inertia::render('User/Login');
})->name('admin.login');

Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/errors', function () {
        return Inertia::render('Error', [
            'code' => 500,
            'message' => 'Page not found'
        ]);
    });

    // Default dashboard - redirect based on user role
    Route::get('/', function () {
        $user = Auth::user();
        
        if ($user->isAdmin == 1 || $user->isAdmin === true || $user->role === 'admin') {
            return redirect('/admin/dashboard');
        } elseif ($user->role === 'rescuer') {
            return redirect('/rescuer/dashboard');
        } else {
            return redirect('/user/scanner');
        }
    })->name('dashboard');
    
    // ============================================================
    // Admin Panel Routes
    // ============================================================
    Route::prefix('admin')->name('admin.')->group(function () {
        // Dashboard with analytics
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        
        // Users management
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{id}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])->name('users.delete');
        
        // Rescuers management
        Route::get('/rescuers', [AdminController::class, 'rescuers'])->name('rescuers');
        Route::post('/rescuers', [AdminController::class, 'storeRescuer'])->name('rescuers.store');
        Route::put('/rescuers/{id}', [AdminController::class, 'updateUser'])->name('rescuers.update');
        Route::delete('/rescuers/{id}', [AdminController::class, 'deleteUser'])->name('rescuers.delete');
        
        // Buildings management
        Route::get('/buildings', [AdminController::class, 'buildings'])->name('buildings');
        
        // Reports
        Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
        
        // Preventive Measures admin
        Route::get('/preventive-measures', [PreventiveMeasureController::class, 'adminIndex'])->name('preventive-measures');
    });

    // Building, Floor, Room routes
    Route::resource('buildings', BuildingController::class)->except(['create', 'edit']);
    Route::post('buildings/{building}/floors', [BuildingController::class, 'addFloor']);
    Route::post('buildings/{building}/rooms', [BuildingController::class, 'addRoom']);

    Route::resource('floors', FloorController::class)->except(['create', 'edit']);
    
    Route::resource('rooms', RoomController::class)->except(['create', 'edit']);

    // Rescue Request routes
    Route::resource('rescue-requests', RescueRequestController::class)->except(['create', 'edit']);
    Route::get('rescue-requests/code/{code}', [RescueRequestController::class, 'showByCode']);
    Route::get('rescue-requests/rescuer/{rescuerId}', [RescueRequestController::class, 'rescuerFeed']);
    Route::get('users/{user}/rescue-history', [RescueRequestController::class, 'userHistory']);
    Route::get('location-details/{buildingId}/{floorId}/{roomId}', [RescueRequestController::class, 'getLocationDetails']);
    Route::patch('rescue-requests/code/{code}/status', [RescueRequestController::class, 'updateStatus']);
    Route::post('rescue-requests/{rescueRequest}/mark-safe', [RescueRequestController::class, 'markSafe']);

    // Conversation & Messaging routes
    Route::resource('conversations', ConversationController::class)->except(['create', 'edit']);
    Route::post('conversations/{conversation}/participants', [ConversationController::class, 'addParticipant']);
    Route::post('conversations/{conversation}/read', [ConversationController::class, 'markRead']);

    // Messages routes
    Route::get('conversations/{conversation}/messages', [MessageController::class, 'index']);
    Route::post('conversations/{conversation}/messages', [MessageController::class, 'store']);
    Route::resource('messages', MessageController::class)->except(['create', 'edit', 'index', 'store']);

    // Audit Trail routes
    Route::resource('audit-trails', AuditTrailController::class)->except(['create', 'edit']);
    
    // Preventive Measures management (Admin)
    Route::prefix('preventive-measures')->name('preventive-measures.')->group(function () {
        Route::get('/', [PreventiveMeasureController::class, 'adminIndex'])->name('index');
        Route::post('/', [PreventiveMeasureController::class, 'store'])->name('store');
        Route::get('/{id}', [PreventiveMeasureController::class, 'show'])->name('show');
        Route::put('/{id}', [PreventiveMeasureController::class, 'update'])->name('update');
        Route::delete('/{id}', [PreventiveMeasureController::class, 'destroy'])->name('destroy');
    });
});

