<?php

namespace App\Livewire\Store;

use App\Models\Store;
use App\Notifications\SellerEmailVerificationNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.store')]
class StoreLogin extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    public bool $remember = false;

    /**
     * The login token from the URL.
     */
    public string $token = '';

    public function mount(string $token = ''): void
    {
        $this->token = $token;

        // Validate the token matches the current store
        $store = $this->resolveCurrentStore();

        if (! $store || $store->login_token !== $this->token) {
            abort(404);
        }
    }

    /**
     * Resolve the current store from the subdomain.
     */
    private function resolveCurrentStore(): ?Store
    {
        if (app()->bound('currentStore')) {
            return app('currentStore');
        }

        $host = request()->getHost();
        $domain = config('app.domain');

        if (! str_ends_with($host, '.'.$domain)) {
            return null;
        }

        $slug = str_replace('.'.$domain, '', $host);

        return Store::where('slug', $slug)->first();
    }

    /**
     * Attempt to authenticate the store owner.
     */
    public function authenticate(): void
    {
        $this->validate();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->addError('email', __('auth.failed'));

            return;
        }

        $user = Auth::user();
        $store = $this->resolveCurrentStore();

        if (! $store) {
            Auth::logout();
            $this->addError('email', 'Unable to determine which store you are accessing.');

            return;
        }

        // Verify the authenticated user has access to this store
        $hasAccess = false;

        if ($user->isStoreOwner() && $user->store?->id === $store->id) {
            $hasAccess = true;
        }

        if ($user->isStaff() && $user->store_id === $store->id) {
            $hasAccess = true;
        }

        if (! $hasAccess) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();

            $this->addError('email', 'You do not have access to this store.');

            return;
        }

        if (! $user->hasVerifiedEmail()) {
            $user->notify(new SellerEmailVerificationNotification);

            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();

            $this->addError('email', 'Please verify your email address before accessing your store dashboard. We sent a fresh verification link.');

            return;
        }

        session()->regenerate();

        $this->redirect($store->dashboardPath());
    }

    public function render(): View
    {
        return view('livewire.store.store-login');
    }
}
