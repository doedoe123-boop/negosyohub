<x-layouts.app :title="'Deals & Offers — NegosyoHub Marketplace'">

    {{-- Premium Hero Section --}}
    <div class="relative bg-white dark:bg-[#0B1120] border-b border-slate-200 dark:border-slate-800/60 overflow-hidden" id="deals-hero">
        
        {{-- Decorative Background Gradients --}}
        <div class="absolute inset-0 pointer-events-none overflow-hidden z-0 hidden dark:block">
            <div class="absolute -top-1/4 left-1/4 w-[500px] h-[500px] bg-rose-500/10 rounded-full blur-[100px] mix-blend-screen opacity-50"></div>
            <div class="absolute bottom-1/4 right-0 w-[600px] h-[600px] bg-amber-500/10 rounded-full blur-[120px] mix-blend-screen opacity-40"></div>
        </div>

        {{-- Background Dot Pattern --}}
        <div class="absolute inset-0 z-0 opacity-[0.03] dark:opacity-[0.05] bg-[radial-gradient(#000_1px,transparent_1px)] dark:bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:24px_24px]"></div>

        <div class="relative z-10 max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
            <div class="max-w-2xl text-center md:text-left mx-auto md:mx-0">
                <nav class="flex items-center justify-center md:justify-start gap-2 text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-6">
                    <a href="{{ route('home') }}" class="hover:text-rose-500 dark:hover:text-rose-400 transition-colors">Home</a>
                    <span class="text-slate-300 dark:text-slate-600">/</span>
                    <span class="text-slate-800 dark:text-slate-200">Deals & Offers</span>
                </nav>
                
                <h1 class="text-4xl sm:text-5xl font-black text-slate-900 dark:text-white tracking-tight leading-[1.15]">
                    Exclusive <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-500 to-rose-500">Enterprise Deals</span>
                </h1>
                
                <p class="mt-4 text-base sm:text-lg text-slate-600 dark:text-slate-400 leading-relaxed max-w-xl mx-auto md:mx-0">
                    Discover exclusive wholesale discounts, limited-time sector offers, and special procurement promotions from verified corporate sellers on NegosyoHub.
                </p>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="bg-slate-50/50 dark:bg-[#060A13]">
        <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-14 lg:py-20">

            {{-- Coming Soon Banner --}}
            <div class="group relative mb-16 rounded-[2rem] overflow-hidden shadow-2xl hover:shadow-orange-500/20 transition-all duration-700">
                <div class="absolute inset-0 bg-gradient-to-br from-amber-500 via-orange-500 to-rose-600 z-0"></div>
                <div class="absolute inset-0 bg-[radial-gradient(#fff_1px,transparent_1px)] opacity-[0.15] [background-size:24px_24px] z-0"></div>
                {{-- Animated overlay --}}
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:animate-[shimmer_2s_infinite] z-0 pointer-events-none"></div>

                <div class="relative z-10 p-10 lg:p-14 text-center text-white">
                    <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/20 border border-white/20 text-sm font-bold mb-6 backdrop-blur-md shadow-sm">
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-white"></span>
                        </span>
                        Marketplace Feature Incoming
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight mb-4 text-transparent bg-clip-text bg-gradient-to-b from-white to-white/80">Hot Wholesale Deals Are Coming!</h2>
                    <p class="text-lg text-white/90 max-w-2xl mx-auto font-medium leading-relaxed">Verified sellers will soon be able to post targeted volume discounts, flash sales, and clearance deals designed for everyday shoppers and businesses alike.</p>
                </div>
            </div>

            {{-- Deal Categories Preview --}}
            <div class="mb-20">
                <div class="flex items-center gap-4 mb-8">
                    <h2 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">What to expect</h2>
                    <div class="h-px bg-slate-200 dark:bg-slate-800 flex-1 hidden sm:block"></div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @php
                        $dealTypes = [
                            ['title' => 'Flash Sales', 'desc' => 'High-velocity, time-bound discounts on bulk items. Perfect for rapid procurement.', 'icon' => 'heroicon-o-bolt', 'gradient' => 'from-amber-400 to-orange-500', 'hover' => 'group-hover:shadow-orange-500/20'],
                            ['title' => 'Volume Bundles', 'desc' => 'Tiered pricing and mixed-pallet discounts. Buy more, scale your savings.', 'icon' => 'heroicon-o-archive-box', 'gradient' => 'from-sky-400 to-blue-600', 'hover' => 'group-hover:shadow-blue-500/20'],
                            ['title' => 'Store Promos', 'desc' => 'Supplier-specific verified promotions, digital vouchers, and loyalty drops.', 'icon' => 'heroicon-o-tag', 'gradient' => 'from-emerald-400 to-teal-500', 'hover' => 'group-hover:shadow-emerald-500/20'],
                            ['title' => 'Liquidation Lots', 'desc' => 'End-of-season, overstock, or fleet clearance assets at heavily reduced pricing.', 'icon' => 'heroicon-o-banknotes', 'gradient' => 'from-rose-400 to-pink-600', 'hover' => 'group-hover:shadow-rose-500/20'],
                        ];
                    @endphp
                    @foreach ($dealTypes as $deal)
                        <div class="group relative bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700/50 rounded-3xl p-8 transition-all duration-500 hover:-translate-y-1 shadow-sm {{ $deal['hover'] }} overflow-hidden">
                            {{-- Top glowing line --}}
                            <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r {{ $deal['gradient'] }} scale-x-0 group-hover:scale-x-100 transition-transform duration-500 origin-left"></div>
                            
                            <div class="h-14 w-14 rounded-2xl bg-gradient-to-br {{ $deal['gradient'] }} flex items-center justify-center mb-6 shadow-lg group-hover:scale-110 transition-transform duration-500">
                                <x-dynamic-component :component="$deal['icon']" class="w-6 h-6 text-white" />
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight mb-2">{{ $deal['title'] }}</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">{{ $deal['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Newsletter CTA --}}
            <div id="newsletter-signup" class="relative rounded-3xl bg-slate-900 dark:bg-slate-800 border border-slate-800 dark:border-slate-700/60 p-10 lg:p-14 text-center overflow-hidden shadow-2xl">
                <div class="absolute inset-0 bg-gradient-to-b from-transparent to-amber-500/5 mix-blend-overlay"></div>
                <div class="absolute inset-0 bg-[radial-gradient(#fff_1px,transparent_1px)] opacity-[0.03] [background-size:24px_24px]"></div>

                <div class="relative z-10">
                    <div class="inline-flex items-center justify-center h-16 w-16 rounded-2xl bg-amber-500/20 mb-6 border border-amber-500/30 shadow-inner">
                        <x-heroicon-o-bell-alert class="w-8 h-8 text-amber-400" />
                    </div>
                    <h3 class="text-2xl md:text-3xl font-extrabold text-white tracking-tight mb-3">Stay updated on verified deals and marketplace offers</h3>
                    <p class="text-base text-slate-400 max-w-lg mx-auto mb-8 leading-relaxed">Subscribe to receive launch updates, supplier promotions, flash sale alerts, and newly listed verified marketplace deals.</p>

                    @if (session('newsletter_status'))
                        <div class="max-w-md mx-auto mb-5 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-200">
                            {{ session('newsletter_status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('newsletter.subscribe') }}" class="max-w-md mx-auto flex flex-col sm:flex-row gap-3">
                        @csrf
                        <input type="hidden" name="source" value="deals.index">
                        <input type="hidden" name="redirect_anchor" value="newsletter-signup">
                        <div class="relative flex-1 group/input">
                            <div class="absolute -inset-0.5 bg-gradient-to-r from-amber-400 to-orange-500 rounded-xl blur opacity-20 group-focus-within/input:opacity-50 transition duration-500"></div>
                            <input
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="Business email address"
                                class="relative w-full rounded-xl border border-slate-700 bg-slate-800/80 px-4 py-4 text-base placeholder:text-slate-500 focus:ring-0 focus:border-amber-500 focus:outline-none text-white shadow-inner transition-all"
                                required
                            >
                        </div>
                        <button type="submit" class="px-8 py-4 rounded-xl bg-white hover:bg-slate-100 text-base font-bold text-slate-900 shadow-xl shadow-black/20 transition-all duration-300 transform hover:-translate-y-1 shrink-0">
                            Subscribe
                        </button>
                    </form>

                    @error('email')
                        <p class="mt-4 text-sm font-medium text-rose-300">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
    </div>

</x-layouts.app>
