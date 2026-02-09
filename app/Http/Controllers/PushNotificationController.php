<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PushNotificationController extends Controller
{
    /**
     * Get VAPID public key for client subscription
     */
    public function vapidPublicKey()
    {
        $publicKey = config('services.webpush.public_key');
        
        Log::info('VAPID public key requested', [
            'has_key' => !empty($publicKey),
            'key_length' => strlen($publicKey ?? '')
        ]);

        return response()->json([
            'publicKey' => $publicKey
        ]);
    }

    /**
     * Store a new push subscription
     */
    public function subscribe(Request $request)
    {
        Log::info('Push subscription request received', [
            'endpoint' => substr($request->endpoint ?? '', 0, 80),
            'has_p256dh' => !empty($request->input('keys.p256dh')),
            'has_auth' => !empty($request->input('keys.auth')),
            'encoding' => $request->input('contentEncoding'),
        ]);

        $request->validate([
            'endpoint' => 'required|string|max:500',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $user = Auth::user();
        
        if (!$user) {
            Log::warning('Push subscribe failed: no authenticated user');
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        try {
            // Update or create subscription
            $subscription = PushSubscription::updateOrCreate(
                [
                    'endpoint' => $request->endpoint,
                ],
                [
                    'user_id' => $user->id,
                    'p256dh_key' => $request->input('keys.p256dh'),
                    'auth_key' => $request->input('keys.auth'),
                    'content_encoding' => $request->input('contentEncoding', 'aes128gcm'),
                ]
            );

            Log::info('Push subscription saved', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'subscription_id' => $subscription->id,
                'endpoint' => substr($subscription->endpoint, 0, 80),
                'p256dh_length' => strlen($subscription->p256dh_key),
                'auth_length' => strlen($subscription->auth_key),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Push subscription saved successfully',
                'subscription_id' => $subscription->id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save push subscription', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a push subscription
     */
    public function unsubscribe(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|string',
        ]);

        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        try {
            $deleted = PushSubscription::where('endpoint', $request->endpoint)
                ->where('user_id', $user->id)
                ->delete();

            Log::info('Push subscription removed', [
                'user_id' => $user->id,
                'deleted' => $deleted
            ]);

            return response()->json([
                'success' => true,
                'message' => $deleted ? 'Subscription removed' : 'Subscription not found'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to remove push subscription', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove subscription'
            ], 500);
        }
    }

    /**
     * Send a test notification to the current user
     */
    public function testNotification()
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        try {
            $pushService = new PushNotificationService();
            $result = $pushService->sendTestNotification($user->id);

            return response()->json([
                'success' => $result['success'] > 0,
                'message' => $result['success'] > 0 
                    ? 'Test notification sent successfully' 
                    : 'No subscriptions found or notification failed',
                'result' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Test notification failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Test notification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get subscription status for current user
     */
    public function status()
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $subscriptions = PushSubscription::where('user_id', $user->id)->get();

        return response()->json([
            'success' => true,
            'has_subscription' => $subscriptions->isNotEmpty(),
            'subscription_count' => $subscriptions->count(),
            'subscriptions' => $subscriptions->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'endpoint_preview' => substr($sub->endpoint, 0, 50) . '...',
                    'created_at' => $sub->created_at,
                    'updated_at' => $sub->updated_at,
                ];
            })
        ]);
    }
}
