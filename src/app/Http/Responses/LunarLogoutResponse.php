<?php

namespace App\Http\Responses;

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse;
use Illuminate\Http\RedirectResponse;

class LunarLogoutResponse implements LogoutResponse
{
    /**
     * Redirect users appropriately after logout.
     *
     * - Store owners on a subdomain → subdomain /login
     * - Admin panel (has its own login) → /admin/login
     * - Fallback → main /login
     */
    public function toResponse($request): RedirectResponse
    {
        // If the panel has its own login page (e.g. Admin panel), use it
        if (Filament::hasLogin()) {
            return redirect()->to(Filament::getLoginUrl());
        }

        $host = $request->getHost();
        $appDomain = config('app.domain', 'localhost');

        // If on a store subdomain, redirect to root which auto-redirects to /portal/{token}/login
        if ($host !== $appDomain && str_ends_with($host, '.'.$appDomain)) {
            return redirect()->to(
                $request->getSchemeAndHttpHost().'/'
            );
        }

        // Fallback: redirect to the main app login
        return redirect()->route('login');
    }
}
