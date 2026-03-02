<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStoreSetupComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isStoreOwner()) {
            return $next($request);
        }

        $store = $user->getStoreForPanel();

        // Only enforce for fully approved stores
        if (! $store || ! $store->isApproved()) {
            return $next($request);
        }

        // Already completed
        if ($store->isSetupComplete()) {
            return $next($request);
        }

        // Avoid redirect loops: pass through the setup page and auth routes
        if ($request->routeIs('filament.lunar.pages.setup') ||
            $request->is('*/setup') ||
            $request->is('*/logout') ||
            $request->is('api/*')) {
            return $next($request);
        }

        return redirect($store->dashboardPath().'/setup');
    }
}
