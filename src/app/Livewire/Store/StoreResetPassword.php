<?php

namespace App\Livewire\Store;

use App\Models\Store;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.store')]
class StoreResetPassword extends Component
{
    public string $token = '';

    public string $resetToken = '';

    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|min:8|confirmed')]
    public string $password = '';

    #[Validate('required')]
    public string $password_confirmation = '';

    public function mount(string $token = '', string $resetToken = ''): void
    {
        $this->token = $token;
        $this->resetToken = $resetToken;
        $this->email = request()->query('email', '');

        $store = $this->resolveCurrentStore();

        if (! $store || $store->login_token !== $this->token) {
            abort(404);
        }
    }

    /**
     * Reset the user's password.
     */
    public function resetPassword(): void
    {
        $this->validate();

        $store = $this->resolveCurrentStore();

        if (! $store) {
            $this->addError('email', 'Unable to determine which store you are accessing.');

            return;
        }

        $status = Password::broker()->reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->resetToken,
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            session()->flash('status', __($status));

            $this->redirect('/portal/'.$this->token.'/login');
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
        return view('livewire.store.store-reset-password');
    }
}
