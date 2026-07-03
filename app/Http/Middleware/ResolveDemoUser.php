<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

/**
 * This demo app has no login flow and no roles. Every request is resolved
 * as a single demo user (no session/cookie required) so audit fields
 * (e.g. Purchase::created_by) and policy checks still have a real user to
 * work with, without gating any request behind authentication. Remove this
 * once real accounts/login are needed.
 */
class ResolveDemoUser
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            $demoUser = User::firstOrCreate(
                ['email' => 'demo@example.com'],
                ['name' => 'Demo User', 'password' => Hash::make(bin2hex(random_bytes(16)))]
            );

            Auth::setUser($demoUser);
        }

        return $next($request);
    }
}
