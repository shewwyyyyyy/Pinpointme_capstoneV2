<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushNotificationService
{
    protected ?WebPush $webPush = null;

    /**
     * Get or create the WebPush instance
     */
    protected function getWebPush(): ?WebPush
    {
        if ($this->webPush) {
            return $this->webPush;
        }

        $vapidPublicKey = config('services.webpush.public_key');
        $vapidPrivateKey = config('services.webpush.private_key');
        $vapidSubject = config('services.webpush.subject', config('app.url'));

        Log::info('WebPush initialization', [
            'has_public_key' => !empty($vapidPublicKey),
            'has_private_key' => !empty($vapidPrivateKey),
            'subject' => $vapidSubject,
            'public_key_length' => strlen($vapidPublicKey ?? ''),
        ]);

        if (!$vapidPublicKey || !$vapidPrivateKey) {
            Log::error('VAPID keys not configured - check .env file');
            return null;
        }

        try {
            $auth = [
                'VAPID' => [
                    'subject' => $vapidSubject,
                    'publicKey' => $vapidPublicKey,
                    'privateKey' => $vapidPrivateKey,
                ],
            ];

            $this->webPush = new WebPush($auth);
            $this->webPush->setReuseVAPIDHeaders(true);
            // Set automatic padding for better compatibility
            $this->webPush->setAutomaticPadding(true);

            Log::info('WebPush initialized successfully');
            return $this->webPush;
        } catch (\Exception $e) {
            Log::error('Failed to initialize WebPush', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Send push notification to specific users by role
     */
    public function sendToRole(string $role, array $payload): array
    {
        Log::info('Sending push to role', ['role' => $role]);
        $users = User::where('role', $role)->pluck('id');
        Log::info('Found users with role', ['role' => $role, 'count' => $users->count()]);
        return $this->sendToUsers($users->toArray(), $payload);
    }

    /**
     * Send push notification to specific users
     */
    public function sendToUsers(array $userIds, array $payload): array
    {
        Log::info('Sending push to users', ['user_ids' => $userIds, 'payload' => $payload]);

        $subscriptions = PushSubscription::whereIn('user_id', $userIds)->get();
        
        Log::info('Found subscriptions', ['count' => $subscriptions->count()]);
        
        $results = [
            'success' => 0,
            'failed' => 0,
            'total' => $subscriptions->count(),
        ];

        if ($subscriptions->isEmpty()) {
            Log::warning('No push subscriptions found for users', ['user_ids' => $userIds]);
            return $results;
        }

        $webPush = $this->getWebPush();
        if (!$webPush) {
            Log::error('WebPush not available, cannot send notifications');
            return $results;
        }

        $payloadJson = json_encode($payload);
        Log::info('Payload JSON', ['payload' => $payloadJson, 'length' => strlen($payloadJson)]);

        foreach ($subscriptions as $subscription) {
            try {
                Log::info('Processing subscription', [
                    'id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'endpoint' => substr($subscription->endpoint, 0, 80) . '...',
                    'p256dh_length' => strlen($subscription->p256dh_key ?? ''),
                    'auth_length' => strlen($subscription->auth_key ?? ''),
                    'encoding' => $subscription->content_encoding,
                ]);

                $pushSubscription = Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->p256dh_key,
                    'authToken' => $subscription->auth_key,
                    'contentEncoding' => $subscription->content_encoding ?? 'aes128gcm',
                ]);

                $webPush->queueNotification(
                    $pushSubscription,
                    $payloadJson
                );
                
                Log::info('Notification queued', ['subscription_id' => $subscription->id]);
            } catch (\Exception $e) {
                Log::error('Failed to queue push notification', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $results['failed']++;
            }
        }

        // Send all queued notifications
        Log::info('Flushing push notifications...');
        
        try {
            foreach ($webPush->flush() as $report) {
                $endpoint = $report->getRequest()->getUri()->__toString();
                
                if ($report->isSuccess()) {
                    $results['success']++;
                    Log::info('Push notification sent successfully', [
                        'endpoint' => substr($endpoint, 0, 80),
                        'status' => $report->getResponse()?->getStatusCode()
                    ]);
                } else {
                    $results['failed']++;
                    $reason = $report->getReason();
                    $response = $report->getResponse();
                    
                    Log::error('Push notification failed', [
                        'endpoint' => substr($endpoint, 0, 80),
                        'reason' => $reason,
                        'status_code' => $response?->getStatusCode(),
                        'response_body' => $response?->getBody()?->getContents(),
                        'is_expired' => $report->isSubscriptionExpired()
                    ]);

                    // If subscription is expired or invalid, delete it
                    if ($report->isSubscriptionExpired()) {
                        PushSubscription::where('endpoint', $endpoint)->delete();
                        Log::info('Deleted expired push subscription', ['endpoint' => substr($endpoint, 0, 80)]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error flushing push notifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        Log::info('Push notification results', $results);
        return $results;
    }

    /**
     * Send push notification to a single user
     */
    public function sendToUser(int $userId, array $payload): bool
    {
        $result = $this->sendToUsers([$userId], $payload);
        return $result['success'] > 0;
    }

    /**
     * Send a test notification to verify the setup works
     */
    public function sendTestNotification(int $userId): array
    {
        $payload = [
            'title' => '🔔 Test Notification',
            'body' => 'Push notifications are working! Time: ' . now()->format('H:i:s'),
            'icon' => '/images/logos/pinpointme.png',
            'badge' => '/images/logos/pinpointme.png',
            'tag' => 'test-' . time(),
            'type' => 'test',
            'data' => [
                'type' => 'test',
                'timestamp' => now()->toIso8601String(),
            ]
        ];

        return $this->sendToUsers([$userId], $payload);
    }
}
