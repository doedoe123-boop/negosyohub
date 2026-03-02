<div
    class="flex min-h-screen w-full"
    x-data="{
        storageKey: 'negosyohub_registration_{{ $sector }}',
        persistFields: ['name', 'email', 'phone', 'storeName', 'slug', 'description', 'addressLine', 'city', 'postcode', 'idType', 'step'],
        passwordStrength: 0,
        passwordLabel: '',
        passwordColor: '',

        init() {
            this.restore()
            this.persistFields.forEach(field => {
                this.$watch('$wire.' + field, () => this.save())
            })
            Livewire.on('registration-complete', () => this.clear())
        },

        save() {
            const data = {}
            this.persistFields.forEach(field => { data[field] = this.$wire[field] })
            localStorage.setItem(this.storageKey, JSON.stringify(data))
        },

        restore() {
            try {
                const saved = localStorage.getItem(this.storageKey)
                if (!saved) return
                const data = JSON.parse(saved)
                this.persistFields.forEach(field => {
                    if (data[field] !== undefined && data[field] !== null && data[field] !== '') {
                        this.$wire.set(field, data[field], false)
                    }
                })
            } catch (e) { localStorage.removeItem(this.storageKey) }
        },

        clear() { localStorage.removeItem(this.storageKey) },

        checkStrength(val) {
            let score = 0
            if (!val) { this.passwordStrength = 0; this.passwordLabel = ''; return }
            if (val.length >= 8) score++
            if (/[A-Z]/.test(val)) score++
            if (/[a-z]/.test(val)) score++
            if (/[0-9]/.test(val)) score++
            if (/[^A-Za-z0-9]/.test(val)) score++
            this.passwordStrength = score
            const labels = ['', 'Very Weak', 'Weak', 'Fair', 'Strong', 'Very Strong']
            const colors = ['', 'bg-red-500', 'bg-orange-500', 'bg-amber-500', 'bg-emerald-500', 'bg-green-600']
            this.passwordLabel = labels[score]
            this.passwordColor = colors[score]
        }
    }"
>
    {{-- ===== LEFT SIDEBAR: Steps ===== --}}
    <aside class="hidden lg:flex flex-col w-72 xl:w-80 bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950 text-white px-8 py-10 flex-shrink-0">
        {{-- Logo --}}
        <a href="/" class="inline-flex items-center gap-3 mb-12 group">
            <div class="h-10 w-10 rounded-xl bg-indigo-500 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                <x-heroicon-o-building-storefront class="w-5 h-5 text-white" />
            </div>
            <span class="text-lg font-bold text-white">NegosyoHub</span>
        </a>

        {{-- Title --}}
        <div class="mb-10">
            <h1 class="text-2xl font-bold text-white leading-tight">Become a Supplier</h1>
            <p class="mt-2 text-sm text-slate-400 leading-relaxed">Complete all 5 steps to submit your store application for review.</p>
            @if ($this->sectorModel)
                <div class="mt-3 px-3 py-1.5 rounded-lg bg-white/10">
                    <span class="text-xs font-semibold text-indigo-300">{{ $this->sectorModel->name }}</span>
                    <a href="{{ route('register.sector') }}" class="text-xs text-indigo-400 hover:text-white transition-colors underline">Change</a>
                </div>
            @endif
        </div>

        {{-- Step list --}}
        <nav class="flex flex-col flex-1">
            @php
                $descriptions = [
                    1 => 'Login credentials & contact',
                    2 => 'Your business identity',
                    3 => 'Physical store location',
                    4 => 'Government-issued ID',
                    5 => 'Required business documents',
                ];
                $totalSteps = count($this->stepLabels);
            @endphp

            @foreach ($this->stepLabels as $num => $label)
                {{-- Wrapper: relative so the connector line can be absolutely positioned --}}
                <div class="relative flex items-start gap-3.5 {{ $num < $totalSteps ? 'pb-7' : '' }}">

                    {{-- Connector line: runs from bottom of circle to bottom of wrapper --}}
                    @if ($num < $totalSteps)
                        <div class="absolute left-[15px] top-8 bottom-0 w-0.5 transition-colors duration-300 {{ $step > $num ? 'bg-emerald-500/50' : 'bg-slate-700/70' }}"></div>
                    @endif

                    {{-- Circle (z-10 so it sits above the line) --}}
                    <div class="relative z-10 flex-shrink-0 h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold border-2 transition-all duration-300
                        {{ $step === $num ? 'bg-indigo-500 border-indigo-400 text-white shadow-lg shadow-indigo-500/30' : '' }}
                        {{ $step > $num  ? 'bg-emerald-500 border-emerald-400 text-white' : '' }}
                        {{ $step < $num  ? 'bg-slate-800 border-slate-600 text-slate-500' : '' }}"
                    >
                        @if($step > $num)
                            <x-heroicon-s-check class="w-4 h-4" />
                        @else
                            {{ $num }}
                        @endif
                    </div>

                    {{-- Label + description --}}
                    <button
                        type="button"
                        wire:click="{{ $num < $step ? 'goToStep('.$num.')' : '' }}"
                        @if($num > $step) disabled @endif
                        class="flex flex-col text-left pt-0.5 transition-all duration-200 {{ $num > $step ? 'cursor-not-allowed' : ($num < $step ? 'cursor-pointer' : '') }}"
                    >
                        <span class="text-xs font-semibold transition-colors duration-200
                            {{ $step === $num ? 'text-white' : '' }}
                            {{ $step > $num  ? 'text-slate-300' : '' }}
                            {{ $step < $num  ? 'text-slate-500' : '' }}">{{ $label }}</span>
                        <span class="text-[11px] text-slate-500 mt-0.5">{{ $descriptions[$num] }}</span>
                    </button>

                </div>
            @endforeach
        </nav>


        {{-- Trust badge --}}
        <div class="mt-auto pt-8 border-t border-slate-700/50">
            <div class="flex items-center gap-2 text-slate-400">
                <x-heroicon-s-lock-closed class="w-4 h-4 text-emerald-400 flex-shrink-0" />
                <p class="text-xs">All data encrypted with AES-256-CBC</p>
            </div>
            <div class="flex items-center gap-2 text-slate-400 mt-2">
                <x-heroicon-s-clock class="w-4 h-4 text-indigo-400 flex-shrink-0" />
                <p class="text-xs">Review takes 3–5 business days</p>
            </div>
        </div>
    </aside>

    {{-- ===== RIGHT PANEL: Form ===== --}}
    <main class="flex-1 flex flex-col min-h-screen bg-slate-50 dark:bg-slate-900">
        {{-- Mobile header --}}
        <div class="lg:hidden flex items-center justify-between px-5 py-4 bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800">
            <a href="/" class="inline-flex items-center gap-2">
                <div class="h-8 w-8 rounded-lg bg-indigo-500 flex items-center justify-center">
                    <x-heroicon-o-building-storefront class="w-4 h-4 text-white" />
                </div>
                <span class="font-bold text-slate-800 dark:text-white">NegosyoHub</span>
            </a>
            <span class="text-xs font-medium text-slate-500">Step {{ $step }} of 5</span>
        </div>

        {{-- Mobile progress bar --}}
        <div class="lg:hidden h-1 bg-slate-200 dark:bg-slate-800">
            <div class="h-full bg-indigo-500 transition-all duration-500 ease-out" style="width: {{ (($step - 1) / 4) * 100 }}%"></div>
        </div>

        <div class="flex-1 flex flex-col justify-center px-5 sm:px-10 xl:px-16 py-10 max-w-2xl mx-auto w-full">

            {{-- Step heading --}}
            <div class="mb-8">
                <p class="text-xs font-semibold text-indigo-500 uppercase tracking-widest mb-1">Step {{ $step }} of 5</p>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
                    @switch($step)
                        @case(1) Account Information @break
                        @case(2) Store Information @break
                        @case(3) Store Address @break
                        @case(4) Identity Verification @break
                        @case(5) Compliance Documents @break
                    @endswitch
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    @switch($step)
                        @case(1) Set up your login credentials and contact details. @break
                        @case(2) Tell customers who you are and what you sell. @break
                        @case(3) Where is your store physically located? @break
                        @case(4) Provide a valid government-issued ID for identity verification. @break
                        @case(5) Upload the required documents for the <strong class="text-indigo-600">{{ $this->sectorModel?->name }}</strong> sector. @break
                    @endswitch
                </p>
            </div>

            <form wire:submit="{{ $step === 5 ? 'register' : 'nextStep' }}" class="space-y-5">

                {{-- ── Step 1: Account ── --}}
                @if ($step === 1)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Full Name --}}
                        <div class="sm:col-span-2">
                            <label for="name" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Full Name</label>
                            <input wire:model="name" type="text" id="name"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-3 text-sm text-slate-900 dark:text-white placeholder-slate-400 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none"
                                placeholder="Juan Dela Cruz">
                            @error('name') <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1"><x-heroicon-s-exclamation-circle class="w-3 h-3" />{{ $message }}</p> @enderror
                        </div>

                        {{-- Email --}}
                        <div class="sm:col-span-2">
                            <label for="email" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Email Address</label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                                    <x-heroicon-o-envelope class="w-4 h-4" />
                                </span>
                                <input wire:model="email" type="email" id="email"
                                    class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 pl-10 pr-4 py-3 text-sm text-slate-900 dark:text-white placeholder-slate-400 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none"
                                    placeholder="you@example.com">
                            </div>
                            @error('email') <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1"><x-heroicon-s-exclamation-circle class="w-3 h-3" />{{ $message }}</p> @enderror
                        </div>

                        {{-- Phone --}}
                        <div class="sm:col-span-2">
                            <label for="phone" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Phone Number</label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                                    <x-heroicon-o-phone class="w-4 h-4" />
                                </span>
                                <input wire:model="phone" type="tel" id="phone"
                                    class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 pl-10 pr-4 py-3 text-sm text-slate-900 dark:text-white placeholder-slate-400 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none"
                                    placeholder="09171234567">
                            </div>
                            @error('phone') <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1"><x-heroicon-s-exclamation-circle class="w-3 h-3" />{{ $message }}</p> @enderror
                        </div>

                        {{-- Password --}}
                        <div>
                            <label for="password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Password</label>
                            <input
                                wire:model="password"
                                @input="checkStrength($event.target.value)"
                                type="password" id="password"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-3 text-sm text-slate-900 dark:text-white placeholder-slate-400 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none"
                                placeholder="Min. 8 characters">
                            {{-- Strength meter --}}
                            <div class="mt-2 space-y-1" x-show="passwordStrength > 0">
                                <div class="flex gap-1">
                                    <template x-for="i in 5">
                                        <div class="h-1 flex-1 rounded-full transition-all duration-300"
                                            :class="i <= passwordStrength ? passwordColor : 'bg-slate-200 dark:bg-slate-700'"></div>
                                    </template>
                                </div>
                                <p class="text-xs font-medium" :class="{
                                    'text-red-500': passwordStrength <= 1,
                                    'text-orange-500': passwordStrength === 2,
                                    'text-amber-500': passwordStrength === 3,
                                    'text-emerald-600': passwordStrength >= 4
                                }" x-text="passwordLabel"></p>
                            </div>
                            <p class="mt-1.5 text-[11px] text-slate-400">Must include uppercase, lowercase, number & symbol.</p>
                            @error('password') <p class="mt-1 text-xs text-red-500 flex items-center gap-1"><x-heroicon-s-exclamation-circle class="w-3 h-3" />{{ $message }}</p> @enderror
                        </div>

                        {{-- Confirm Password --}}
                        <div>
                            <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Confirm Password</label>
                            <input wire:model="password_confirmation" type="password" id="password_confirmation"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-3 text-sm placeholder-slate-400 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none text-slate-900 dark:text-white"
                                placeholder="Re-enter password">
                        </div>
                    </div>
                @endif

                {{-- ── Step 2: Store Info ── --}}
                @if ($step === 2)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="storeName" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Store Name</label>
                            <input wire:model.live.debounce.300ms="storeName" type="text" id="storeName"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-3 text-sm placeholder-slate-400 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none text-slate-900 dark:text-white"
                                placeholder="Store name or Agency">
                            @error('storeName') <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1"><x-heroicon-s-exclamation-circle class="w-3 h-3" />{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="slug" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Store URL Slug</label>
                            <div class="relative">
                                <input wire:model="slug" type="text" id="slug" readonly
                                    class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 px-4 py-3 text-sm text-slate-500 dark:text-slate-400 shadow-sm cursor-not-allowed outline-none">
                            </div>
                            @if ($slug)
                                <p class="mt-1.5 text-[11px] text-slate-500">
                                    URL: <span class="font-mono text-indigo-600 font-semibold">{{ $slug }}.{{ config('app.domain') }}</span>
                                </p>
                            @endif
                            @error('slug') <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1"><x-heroicon-s-exclamation-circle class="w-3 h-3" />{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="description" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Store Description</label>
                            <textarea wire:model="description" id="description" rows="4"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-3 text-sm placeholder-slate-400 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none resize-none text-slate-900 dark:text-white"
                                placeholder="Tell customers what makes your business special and what products or services you offer..."></textarea>
                            @error('description') <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1"><x-heroicon-s-exclamation-circle class="w-3 h-3" />{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif

                {{-- ── Step 3: Address ── --}}
                @if ($step === 3)
                    <div class="space-y-4">
                        <div>
                            <label for="addressLine" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Street Address</label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                                    <x-heroicon-o-map-pin class="w-4 h-4" />
                                </span>
                                <input wire:model="addressLine" type="text" id="addressLine"
                                    class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 pl-10 pr-4 py-3 text-sm placeholder-slate-400 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none text-slate-900 dark:text-white"
                                    placeholder="123 Main Street, Barangay Name">
                            </div>
                            @error('addressLine') <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1"><x-heroicon-s-exclamation-circle class="w-3 h-3" />{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="city" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">City / Municipality</label>
                                <input wire:model="city" type="text" id="city"
                                    class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-3 text-sm placeholder-slate-400 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none text-slate-900 dark:text-white"
                                    placeholder="Manila">
                                @error('city') <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1"><x-heroicon-s-exclamation-circle class="w-3 h-3" />{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="postcode" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Postal Code</label>
                                <input wire:model="postcode" type="text" id="postcode"
                                    class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-3 text-sm placeholder-slate-400 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none text-slate-900 dark:text-white"
                                    placeholder="1000">
                                @error('postcode') <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1"><x-heroicon-s-exclamation-circle class="w-3 h-3" />{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ── Step 4: Identity ── --}}
                @if ($step === 4)
                    {{-- Security notice --}}
                    <div class="flex items-start gap-3 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
                        <x-heroicon-s-lock-closed class="w-5 h-5 text-emerald-600 dark:text-emerald-400 mt-0.5 flex-shrink-0" />
                        <div>
                            <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">Your data is encrypted & secure</p>
                            <p class="text-xs text-emerald-700 dark:text-emerald-400 mt-0.5">Your ID number and personal data are encrypted using AES-256-CBC before storage. Only authorized administrators can access your verification details during review.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="idType" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">ID Type</label>
                            <select wire:model.live="idType" id="idType"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none text-slate-900 dark:text-white">
                                <option value="">Select ID type…</option>
                                @foreach(\App\PhilippineIdType::cases() as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                            @error('idType') <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1"><x-heroicon-s-exclamation-circle class="w-3 h-3" />{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="idNumber" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">ID Number</label>
                            <input wire:model.live.debounce.500ms="idNumber" type="text" id="idNumber"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-3 text-sm placeholder-slate-400 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none text-slate-900 dark:text-white"
                                placeholder="{{ $this->idFormatHint ?: 'Select an ID type first' }}">
                            @if($this->idFormatHint)
                                <p class="mt-1.5 text-[11px] text-slate-400">Format: <span class="font-mono text-indigo-600">{{ $this->idFormatHint }}</span></p>
                            @endif
                            @error('idNumber') <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1"><x-heroicon-s-exclamation-circle class="w-3 h-3" />{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif

                {{-- ── Step 5: Compliance Docs ── --}}
                @if ($step === 5)
                    <div class="space-y-1.5 mb-2">
                        <div class="flex items-start gap-3 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
                            <x-heroicon-s-lock-closed class="w-4 h-4 text-emerald-600 dark:text-emerald-400 mt-0.5 flex-shrink-0" />
                            <p class="text-xs text-emerald-700 dark:text-emerald-400"><span class="font-semibold text-emerald-800 dark:text-emerald-300">Documents are encrypted.</span> All files are encrypted with AES-256-CBC before being stored. Only authorized administrators can view them during review.</p>
                        </div>
                        <div class="flex items-start gap-3 p-3.5 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                            <x-heroicon-s-exclamation-triangle class="w-4 h-4 text-amber-600 mt-0.5 flex-shrink-0" />
                            <p class="text-xs text-amber-700 dark:text-amber-400">Items marked <span class="text-red-500 font-bold">*</span> are required. Accepted: PDF, JPG, JPEG, PNG — max 5MB each.</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @foreach ($this->sectorDocuments as $doc)
                            <div class="rounded-xl border-2 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/50 transition-all duration-200 p-4">
                                <div class="flex items-start justify-between gap-3 mb-3">
                                    <div class="flex items-start gap-2.5">
                                        <div class="mt-0.5 flex-shrink-0 h-7 w-7 rounded-lg {{ $doc['required'] ? 'bg-indigo-50 dark:bg-indigo-900/40' : 'bg-slate-100 dark:bg-slate-700' }} flex items-center justify-center">
                                            <x-heroicon-o-document-text class="w-4 h-4 {{ $doc['required'] ? 'text-indigo-500' : 'text-slate-400' }}" />
                                        </div>
                                        <div>
                                            <label for="doc_{{ $doc['key'] }}" class="text-sm font-semibold text-slate-800 dark:text-slate-200 cursor-pointer">
                                                {{ $doc['label'] }}
                                                @if ($doc['required'])
                                                    <span class="text-red-500">*</span>
                                                @else
                                                    <span class="ml-1 text-[10px] font-normal text-slate-400 bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded-full">Optional</span>
                                                @endif
                                            </label>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $doc['description'] }}</p>
                                        </div>
                                    </div>

                                    @if (isset($complianceFiles[$doc['key']]) && $complianceFiles[$doc['key']])
                                        <span class="inline-flex items-center gap-1 text-[11px] text-emerald-700 dark:text-emerald-400 font-semibold bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-700 px-2.5 py-1 rounded-full flex-shrink-0">
                                            <x-heroicon-s-check-circle class="w-3.5 h-3.5" />
                                            Uploaded
                                        </span>
                                    @endif
                                </div>

                                <label for="doc_{{ $doc['key'] }}"
                                    class="flex items-center justify-center gap-2 w-full rounded-lg border border-dashed border-slate-300 dark:border-slate-600 px-4 py-3.5 cursor-pointer hover:border-indigo-400 hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 transition-all duration-150 group">
                                    <x-heroicon-o-arrow-up-tray class="w-4 h-4 text-slate-400 group-hover:text-indigo-500 transition-colors" />
                                    <span class="text-sm text-slate-500 dark:text-slate-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                        @if (isset($complianceFiles[$doc['key']]) && $complianceFiles[$doc['key']])
                                            Replace file
                                        @else
                                            Click to upload
                                        @endif
                                    </span>
                                    <input
                                        wire:model="complianceFiles.{{ $doc['key'] }}"
                                        type="file"
                                        id="doc_{{ $doc['key'] }}"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        class="sr-only">
                                </label>

                                <div wire:loading wire:target="complianceFiles.{{ $doc['key'] }}" class="mt-2 flex items-center gap-1.5 text-xs text-indigo-600">
                                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Uploading…
                                </div>

                                @error("complianceFiles.{$doc['key']}")
                                    <p class="mt-2 text-xs text-red-500 flex items-center gap-1">
                                        <x-heroicon-s-exclamation-circle class="w-3 h-3" />{{ $message }}
                                    </p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                @endif


                {{-- ── Navigation Buttons ── --}}
                <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-800 mt-6">
                    <div>
                        @if ($step > 1)
                            <button type="button" wire:click="previousStep"
                                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm transition-all duration-150">
                                <x-heroicon-o-arrow-left class="w-4 h-4" />
                                Back
                            </button>
                        @else
                            <a href="{{ route('register.sector') }}" class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                                ← Change sector
                            </a>
                        @endif
                    </div>

                    <div class="flex items-center gap-3">
                        {{-- Step dots (mobile) --}}
                        <div class="flex items-center gap-1 lg:hidden">
                            @for ($i = 1; $i <= 5; $i++)
                                <div class="rounded-full transition-all duration-300 {{ $i === $step ? 'w-4 h-2 bg-indigo-500' : ($i < $step ? 'w-2 h-2 bg-indigo-300' : 'w-2 h-2 bg-slate-200 dark:bg-slate-700') }}"></div>
                            @endfor
                        </div>

                        @if ($step < 5)
                            <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 transition-all duration-150 active:scale-95">
                                Continue
                                <x-heroicon-o-arrow-right class="w-4 h-4" />
                            </button>
                        @else
                            <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 transition-all duration-150 active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed"
                                wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="register" class="inline-flex items-center gap-2">
                                    <x-heroicon-o-paper-airplane class="w-4 h-4" />
                                    Submit Application
                                </span>
                                <span wire:loading wire:target="register" class="inline-flex items-center gap-2">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Submitting…
                                </span>
                            </button>
                        @endif
                    </div>
                </div>
            </form>

            {{-- Legal footer --}}
            @php
                $legalLinks = \App\Models\LegalPage::published()
                    ->whereIn('type', ['terms', 'privacy', 'store_agreement'])
                    ->orderByRaw("CASE type WHEN 'terms' THEN 1 WHEN 'privacy' THEN 2 ELSE 3 END")
                    ->get(['title', 'slug']);
            @endphp
            @if ($legalLinks->isNotEmpty())
                <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-800">
                    <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2">
                        @foreach ($legalLinks as $lp)
                            <a href="{{ route('legal.show', $lp->slug) }}"
                               target="_blank"
                               class="text-xs text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                {{ $lp->title }}
                            </a>
                        @endforeach
                    </div>
                    <p class="mt-3 text-center text-[11px] text-slate-400">© {{ date('Y') }} NegosyoHub. By registering you agree to our Terms &amp; Conditions and Privacy Policy.</p>
                </div>
            @endif
        </div>
    </main>
</div>
