<?php

namespace App\Livewire\Store;

use App\Models\Store;
use App\Notifications\StoreResetPasswordNotification;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.store')]
class StoreForgotPassword extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    /**
     * The login token from the URL.
     */
    public string $token = '';

    public bool $linkSent = false;

    public function mount(string $token = ''): void
    {
        $this->token = $token;

        $store = $this->resolveCurrentStore();

        if (! $store || $store->login_token !== $this->token) {
            abort(404);
        }
    }

    /**
     * Send the password reset link.
     */
    public function sendResetLink(): void
    {
        $this->validate();

        $store = $this->resolveCurrentStore();

        if (! $store) {
            $this->addError('email', 'Unable to determine which store you are accessing.');

            return;
        }

        // Use Laravel's password broker to create a token and send notification
        $status = Password::broker()->sendResetLink(
            ['email' => $this->email],
            function ($user, $resetToken) use ($store) {
                $user->notify(new StoreResetPasswordNotification($resetToken, $store));
            }
        );

        if ($status === Password::RESET_LINK_SENT) {
            $this->linkSent = true;
            session()->flash('status', __($status));
        } else {
            $this->addError('email', __($status));
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

    public function render(): \Illuminate\View\View
    {
        return view('livewire.store.store-forgot-password');
    }
}
