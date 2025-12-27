<?php
// app/Http/Middleware/OptionalAuthenticate.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OptionalAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->bearerToken()) {
            try {
                // Set sanctum as the default guard for this request
                Auth::shouldUse('sanctum');
                
                // Now authenticate using the sanctum guard
                // This will set the authenticated user
                Auth::guard('sanctum')->authenticate();
                
                \Log::info('User authenticated. ID: ' . Auth::id());
            } catch (\Exception $e) {
                // Token is invalid or expired - continue as guest
                \Log::info('Continuing as guest. Token error: ' . $e->getMessage());
            }
        } else {
            \Log::info('No token - guest request');
        }
        
        // Now Auth::user(), Auth::id(), etc. will work correctly
        \Log::info('Final Auth::id(): ' . (Auth::id() ?: 'NULL'));
        
        return $next($request);
    }
}