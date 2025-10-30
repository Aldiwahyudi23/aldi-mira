<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next ,  $role = null): Response
    {
        $user = $request->user();

        // Cek role
        if (! $user || ! $user->hasRole($role)) {
            // Return halaman Inertia langsung (tanpa redirect error)
            return Inertia::render('Errors/Forbidden', [
                'message' => 'Kamu tidak memiliki akses ke halaman ini 😢',
            ])->toResponse($request)->setStatusCode(403);
        }

        return $next($request);
    }
}
