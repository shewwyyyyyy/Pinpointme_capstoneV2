<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPasswordChange
{
    /**
     * Handle an incoming request.
     * Redirect users who need to change their password.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // If user is logged in and needs to change password
        if ($user && $user->force_password_change) {
            // Allow access to password change page and logout
            $allowedRoutes = ['change-password', 'logout'];
            $allowedPaths = ['/change-password', '/logout', '/api/auth/send-password-change-otp', '/api/auth/verify-password-change-otp', '/api/auth/complete-password-change'];
            
            if (!in_array($request->route()?->getName(), $allowedRoutes) && !in_array($request->path(), $allowedPaths) && !str_starts_with($request->path(), 'api/auth/')) {
                return redirect()->route('change-password');
            }
        }
        
        return $next($request);
    }
}
