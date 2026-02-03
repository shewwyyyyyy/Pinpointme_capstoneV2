<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return
            array_merge(parent::share($request), [
                'appVersion' => env('APP_VERSION', '1.0.0'),
                'appName' => env('APP_NAME', 'Astoria QR Scanner'),
                'appDeveloper' => env('APP_DEVELOPER', 'ICT'),
                'flash' => function () use ($request) {
                    return [
                        'message' => $request->session()->get('message'),
                        'success' => $request->session()->get('success'),
                        'error' => $request->session()->get('error'),
                    ];
                },
                'auth' => Auth::check() ? [
                    'user' => [
                        'id' => Auth::user()->id,
                        'username' => Auth::user()->username,
                        'email' => Auth::user()->email,
                        'first_name' => Auth::user()->first_name ?? '',
                        'last_name' => Auth::user()->last_name ?? '',
                        'role' => Auth::user()->role ?? 'student',
                        'isAdmin' => (bool) (Auth::user()->isAdmin ?? Auth::user()->is_admin ?? false),
                        'profile_picture' => Auth::user()->profile_picture ?? null,
                        'contact_number' => Auth::user()->contact_number ?? '',
                        'phone_number' => Auth::user()->phone_number ?? Auth::user()->phone ?? '',
                        'emergency_contact_name' => Auth::user()->emergency_contact_name ?? '',
                        'emergency_contact_phone' => Auth::user()->emergency_contact_phone ?? '',
                        'emergency_contact_relation' => Auth::user()->emergency_contact_relation ?? '',
                        'blood_type' => Auth::user()->blood_type ?? '',
                        'allergies' => Auth::user()->allergies ?? '',
                        'medical_conditions' => Auth::user()->medical_conditions ?? '',
                    ]
                ] : null,
            ]);
    }
}
