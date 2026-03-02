<div>
    <h2 class="text-2xl font-bold text-center text-slate-800 dark:text-white mb-1">Forgot Password</h2>
    <p class="text-center text-sm text-slate-500 dark:text-slate-400 mb-6">
        Enter your email and we'll send you a reset link.
    </p>

    @if ($linkSent)
        <div class="mb-4 text-sm text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 p-3.5 rounded-xl border border-emerald-200 dark:border-emerald-700">
            A password reset link has been sent to your email address. Please check your inbox.
        </div>
    @else
        @if (session('status'))
            <div class="mb-4 text-sm text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 p-3.5 rounded-xl border border-emerald-200 dark:border-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <form wire:submit="sendResetLink" class="space-y-5">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Email</label>
                <input
                    wire:model="email"
                    id="email"
                    type="email"
                    required
                    autofocus
                    class="block w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm focus:border-sky-500 focus:ring-sky-500 px-4 py-2.5 text-sm transition-colors duration-200"
                >
                @error('email')
                    <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button type="submit"
                    class="w-full py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 dark:bg-sky-500 dark:hover:bg-sky-600 shadow-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800 transition-colors duration-200"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="sendResetLink">Send Reset Link</span>
                    <span wire:loading wire:target="sendResetLink" class="inline-flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Sending...
                    </span>
                </button>
            </div>
        </form>
    @endif

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
        Remember your password?
        <a href="/portal/{{ $token }}/login"
           class="font-medium text-sky-600 hover:text-sky-700 dark:text-sky-400 dark:hover:text-sky-300 transition-colors duration-200">
            Back to sign in
        </a>
    </p>
</div>
