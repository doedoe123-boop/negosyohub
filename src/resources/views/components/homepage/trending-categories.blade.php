{{-- Industry Sectors — Premium Dashboard Showcase --}}
<div class="relative bg-slate-50 dark:bg-[#0B1120] border-b border-slate-200 dark:border-slate-800/60 overflow-hidden" id="industry-sectors" x-data="{ activeTab: 'ecommerce' }">
    {{-- Decorative Background --}}
    <div class="absolute inset-0 pointer-events-none overflow-hidden z-0 hidden dark:block">
        <div class="absolute top-10 right-[10%] w-[400px] h-[400px] bg-emerald-500/5 rounded-full blur-[100px]"></div>
        <div class="absolute bottom-10 left-[10%] w-[400px] h-[400px] bg-sky-500/5 rounded-full blur-[100px]"></div>
    </div>

    <div class="relative z-10 max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28">
        {{-- Section Header --}}
        <div class="text-center mb-14">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 mb-5 shadow-sm">
                <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-[0.15em]">Ecosystem</span>
            </div>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 dark:text-white tracking-tight leading-[1.15]">
                Built for your industry.
            </h2>
            <p class="mt-5 mb-5 text-base sm:text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto leading-relaxed">
                NegosyoHub isn't a one-size-fits-all directory. We power dedicated dashboards for each vertical — intelligent tools tailored to how your industry actually works.
            </p>
        </div>

        {{-- Tab Switcher --}}
        <div class="flex items-center justify-center gap-2 mb-10">
            <button @click="activeTab = 'ecommerce'"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-300"
                    :class="activeTab === 'ecommerce'
                        ? 'bg-slate-900 dark:bg-white text-white dark:text-slate-900 shadow-lg'
                        : 'bg-white dark:bg-slate-800/50 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-500'">
                <x-heroicon-o-shopping-cart class="w-4 h-4" />
                E-Commerce
            </button>
            <button @click="activeTab = 'realestate'"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-300"
                    :class="activeTab === 'realestate'
                        ? 'bg-slate-900 dark:bg-white text-white dark:text-slate-900 shadow-lg'
                        : 'bg-white dark:bg-slate-800/50 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-500'">
                <x-heroicon-o-home-modern class="w-4 h-4" />
                Real Estate
            </button>
        </div>

        {{-- ============================================================
             E-COMMERCE: Lunar Dark Dashboard
             ============================================================ --}}
        <div x-show="activeTab === 'ecommerce'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="max-w-6xl mx-auto">
            <div class="rounded-2xl overflow-hidden shadow-2xl shadow-black/30 border border-slate-700/50 bg-[#0a0a12]">
                <div class="flex min-h-[520px]">
                    {{-- Sidebar --}}
                    <div class="hidden sm:flex w-16 flex-col items-center py-6 gap-6 border-r border-slate-800/60 bg-[#08080e]">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                            <x-heroicon-s-squares-2x2 class="w-4 h-4 text-emerald-400" />
                        </div>
                        <div class="w-8 h-8 rounded-lg hover:bg-slate-800 flex items-center justify-center transition-colors cursor-pointer">
                            <x-heroicon-o-shopping-bag class="w-4 h-4 text-slate-500" />
                        </div>
                        <div class="w-8 h-8 rounded-lg hover:bg-slate-800 flex items-center justify-center transition-colors cursor-pointer">
                            <x-heroicon-o-clipboard-document-list class="w-4 h-4 text-slate-500" />
                        </div>
                        <div class="w-8 h-8 rounded-lg hover:bg-slate-800 flex items-center justify-center transition-colors cursor-pointer">
                            <x-heroicon-o-users class="w-4 h-4 text-slate-500" />
                        </div>
                        <div class="w-8 h-8 rounded-lg hover:bg-slate-800 flex items-center justify-center transition-colors cursor-pointer mt-auto">
                            <x-heroicon-o-cog-6-tooth class="w-4 h-4 text-slate-500" />
                        </div>
                    </div>

                    {{-- Main Content --}}
                    <div class="flex-1 p-5 sm:p-6">
                        {{-- Header --}}
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-base font-bold text-white">Dashboard Overview</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Welcome back to NegosyoHub</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-800/60 border border-slate-700/50">
                                    <x-heroicon-o-magnifying-glass class="w-3.5 h-3.5 text-slate-500" />
                                    <span class="text-xs text-slate-500">Search...</span>
                                </div>
                                <div class="w-7 h-7 rounded-full bg-emerald-500/20 flex items-center justify-center">
                                    <x-heroicon-s-user class="w-3.5 h-3.5 text-emerald-400" />
                                </div>
                            </div>
                        </div>

                        {{-- KPI Cards --}}
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
                            @php
                                $kpis = [
                                    ['label' => 'Total Revenue',    'value' => '₱847K',  'change' => '+12%', 'positive' => true,  'icon' => 'heroicon-o-currency-dollar'],
                                    ['label' => 'Active Orders',    'value' => '127',     'change' => '+8%',  'positive' => true,  'icon' => 'heroicon-o-shopping-cart'],
                                    ['label' => 'Customers',        'value' => '2,847',   'change' => '+15%', 'positive' => true,  'icon' => 'heroicon-o-users'],
                                    ['label' => 'Conversion Rate',  'value' => '24.3%',   'change' => '+5%',  'positive' => true,  'icon' => 'heroicon-o-arrow-trending-up'],
                                ];
                            @endphp
                            @foreach($kpis as $kpi)
                                <div class="rounded-xl bg-slate-800/40 border border-slate-700/40 p-3.5 backdrop-blur-sm">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ $kpi['label'] }}</span>
                                        <x-dynamic-component :component="$kpi['icon']" class="w-4 h-4 text-slate-600" />
                                    </div>
                                    <div class="text-xl font-black text-white">{{ $kpi['value'] }}</div>
                                    <div class="mt-1 inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold {{ $kpi['positive'] ? 'text-emerald-400 bg-emerald-500/10' : 'text-red-400 bg-red-500/10' }}">
                                        {{ $kpi['change'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Revenue Chart --}}
                        <div class="rounded-xl bg-slate-800/40 border border-slate-700/40 p-4 mb-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h4 class="text-sm font-bold text-white">Revenue Overview</h4>
                                    <p class="text-[10px] text-slate-500 mt-0.5">Jan – Jun Performance</p>
                                </div>
                                <div class="flex items-center gap-1 px-2 py-1 rounded-md bg-slate-700/50 text-[10px] text-slate-400 font-medium">
                                    Last 6 months
                                </div>
                            </div>
                            {{-- SVG Area Chart --}}
                            <div class="relative h-32 sm:h-40">
                                <svg viewBox="0 0 600 160" class="w-full h-full" preserveAspectRatio="none">
                                    <defs>
                                        <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#10b981" stop-opacity="0.3"/>
                                            <stop offset="100%" stop-color="#10b981" stop-opacity="0"/>
                                        </linearGradient>
                                    </defs>
                                    {{-- Grid lines --}}
                                    <line x1="0" y1="40" x2="600" y2="40" stroke="#1e293b" stroke-width="0.5"/>
                                    <line x1="0" y1="80" x2="600" y2="80" stroke="#1e293b" stroke-width="0.5"/>
                                    <line x1="0" y1="120" x2="600" y2="120" stroke="#1e293b" stroke-width="0.5"/>
                                    {{-- Area fill --}}
                                    <path d="M0,120 C50,110 100,95 150,80 C200,65 250,70 300,55 C350,40 400,50 450,35 C500,20 550,25 600,15 L600,160 L0,160 Z" fill="url(#chartGrad)"/>
                                    {{-- Line --}}
                                    <path d="M0,120 C50,110 100,95 150,80 C200,65 250,70 300,55 C350,40 400,50 450,35 C500,20 550,25 600,15" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round"/>
                                    {{-- Dot --}}
                                    <circle cx="600" cy="15" r="4" fill="#10b981"/>
                                    <circle cx="600" cy="15" r="7" fill="#10b981" opacity="0.2"/>
                                </svg>
                                {{-- Month labels --}}
                                <div class="absolute bottom-0 inset-x-0 flex justify-between px-1 text-[9px] font-medium text-slate-600">
                                    <span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span><span>May</span><span>Jun</span>
                                </div>
                            </div>
                        </div>

                        {{-- Bottom Row --}}
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            {{-- Top Products --}}
                            <div class="rounded-xl bg-slate-800/40 border border-slate-700/40 p-4">
                                <h4 class="text-sm font-bold text-white mb-3">Top Products</h4>
                                @php
                                    $products = [
                                        ['name' => 'Premium Wireless Earbuds', 'sales' => '₱128K', 'pct' => 85],
                                        ['name' => 'Mechanical Keyboard',      'sales' => '₱96K',  'pct' => 65],
                                        ['name' => 'Smart Home Hub',           'sales' => '₱74K',  'pct' => 50],
                                        ['name' => 'Ergonomic Mouse',          'sales' => '₱52K',  'pct' => 35],
                                    ];
                                @endphp
                                <div class="space-y-3">
                                    @foreach($products as $p)
                                        <div class="flex items-center gap-3">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="text-xs text-slate-300 font-medium truncate">{{ $p['name'] }}</span>
                                                    <span class="text-xs text-slate-400 font-bold ml-2 shrink-0">{{ $p['sales'] }}</span>
                                                </div>
                                                <div class="h-1.5 rounded-full bg-slate-700/60 overflow-hidden">
                                                    <div class="h-full rounded-full bg-emerald-500/80" style="width: {{ $p['pct'] }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Recent Orders --}}
                            <div class="rounded-xl bg-slate-800/40 border border-slate-700/40 p-4">
                                <h4 class="text-sm font-bold text-white mb-3">Recent Orders</h4>
                                @php
                                    $orders = [
                                        ['id' => '#84729', 'time' => '2 mins ago',  'status' => 'Completed', 'color' => 'emerald'],
                                        ['id' => '#84730', 'time' => '15 mins ago', 'status' => 'Pending',   'color' => 'amber'],
                                        ['id' => '#84728', 'time' => '1 hour ago',  'status' => 'Shipped',   'color' => 'sky'],
                                        ['id' => '#84727', 'time' => '3 hours ago', 'status' => 'Completed', 'color' => 'emerald'],
                                    ];
                                @endphp
                                <div class="space-y-2.5">
                                    @foreach($orders as $o)
                                        <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-900/40 border border-slate-700/30">
                                            <div>
                                                <span class="text-xs font-bold text-white">Order {{ $o['id'] }}</span>
                                                <span class="text-[10px] text-slate-500 ml-2">{{ $o['time'] }}</span>
                                            </div>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-{{ $o['color'] }}-500/10 text-{{ $o['color'] }}-400">{{ $o['status'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Caption under dashboard --}}
            <div class="text-center mt-8">
                <p class="text-sm font-bold text-slate-900 dark:text-white">Seller Dashboard — E-Commerce</p>
                <p class="text-xs text-slate-500 dark:text-slate-500 mt-1">Real-time revenue tracking, product analytics, and order management — built for online sellers.</p>
                <a href="{{ route('register.sector') }}" class="inline-flex items-center gap-1.5 mt-4 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-lg shadow-emerald-600/20 transition-all duration-300">
                    Start Selling <x-heroicon-o-arrow-right class="w-4 h-4" />
                </a>
            </div>
        </div>

        {{-- ============================================================
             REAL ESTATE: Luxury Light Dashboard
             ============================================================ --}}
        <div x-show="activeTab === 'realestate'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="max-w-6xl mx-auto">
            <div class="rounded-2xl overflow-hidden shadow-2xl shadow-slate-900/10 dark:shadow-black/40 border border-slate-200 dark:border-slate-700/50 bg-[#FCFAF7] dark:bg-[#0f1520]">
                <div class="flex min-h-[520px]">
                    {{-- Sidebar --}}
                    <div class="hidden sm:flex w-48 flex-col border-r border-slate-200 dark:border-slate-700/50 dark:bg-[#0c1018] p-5">
                        <div class="flex items-center gap-2 mb-8">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center">
                                <x-heroicon-s-building-office class="w-4 h-4 text-white" />
                            </div>
                            <span class="text-sm font-extrabold text-slate-900 dark:text-white tracking-tight">LuxeEstates</span>
                        </div>
                        @php
                            $sideLinks = [
                                ['icon' => 'heroicon-o-squares-2x2',          'label' => 'Dashboard',  'active' => true],
                                ['icon' => 'heroicon-o-home-modern',           'label' => 'Properties', 'active' => false],
                                ['icon' => 'heroicon-o-users',                 'label' => 'Leads',      'active' => false],
                                ['icon' => 'heroicon-o-chart-bar',             'label' => 'Analytics',  'active' => false],
                                ['icon' => 'heroicon-o-calendar-days',         'label' => 'Calendar',   'active' => false],
                            ];
                        @endphp
                        <nav class="space-y-1">
                            @foreach($sideLinks as $link)
                                <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors cursor-pointer {{ $link['active'] ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                                    <x-dynamic-component :component="$link['icon']" class="w-4 h-4" />
                                    {{ $link['label'] }}
                                </div>
                            @endforeach
                        </nav>
                        <div class="mt-auto pt-6 flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-amber-100 dark:bg-amber-500/20 flex items-center justify-center">
                                <x-heroicon-s-user class="w-3.5 h-3.5 text-amber-700 dark:text-amber-400" />
                            </div>
                            <div>
                                <div class="text-[11px] font-bold text-slate-700 dark:text-slate-300">Alexander Pierce</div>
                                <div class="text-[9px] text-slate-400">Senior Broker</div>
                            </div>
                        </div>
                    </div>

                    {{-- Main Content --}}
                    <div class="flex-1 p-5 sm:p-6">
                        {{-- Header --}}
                        <div class="mb-6">
                            <h3 class="text-lg font-extrabold text-slate-900 dark:text-white tracking-tight">Overview</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Welcome back, Alexander. Here's your portfolio summary.</p>
                        </div>

                        {{-- KPI Cards --}}
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
                            @php
                                $reKpis = [
                                    ['label' => 'Portfolio Value',    'value' => '₱45.8M',  'sub' => '↗ Trending up', 'accent' => 'emerald'],
                                    ['label' => 'Active Listings',    'value' => '24',       'sub' => 'Properties',     'accent' => 'sky'],
                                    ['label' => 'Leads This Month',   'value' => '89',       'sub' => '+23%',           'accent' => 'emerald'],
                                    ['label' => 'Avg Days on Market', 'value' => '18',       'sub' => 'Days',           'accent' => 'amber'],
                                ];
                            @endphp
                            @foreach($reKpis as $kpi)
                                <div class="rounded-xl bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700/40 p-3.5 shadow-sm">
                                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-500 uppercase tracking-wider">{{ $kpi['label'] }}</span>
                                    <div class="text-xl font-black text-slate-900 dark:text-white mt-1">{{ $kpi['value'] }}</div>
                                    <div class="text-[10px] font-bold text-{{ $kpi['accent'] }}-600 dark:text-{{ $kpi['accent'] }}-400 mt-1">{{ $kpi['sub'] }}</div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Charts Row --}}
                        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 mb-6">
                            {{-- Property Performance (3/5) --}}
                            <div class="lg:col-span-3 rounded-xl bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700/40 p-4 shadow-sm">
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Property Performance</h4>
                                @php
                                    $props = [
                                        ['name' => 'The Crown Villa',     'sold' => 70, 'active' => 20, 'pending' => 10],
                                        ['name' => 'Skyline Penthouse',   'sold' => 0,  'active' => 80, 'pending' => 20],
                                        ['name' => 'Azure Estate',        'sold' => 0,  'active' => 60, 'pending' => 40],
                                        ['name' => 'Palm Bay Residence',  'sold' => 90, 'active' => 0,  'pending' => 10],
                                        ['name' => 'BGC Tower Suite',     'sold' => 0,  'active' => 50, 'pending' => 50],
                                    ];
                                @endphp
                                <div class="space-y-3">
                                    @foreach($props as $p)
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-[11px] text-slate-600 dark:text-slate-400 font-medium">{{ $p['name'] }}</span>
                                            </div>
                                            <div class="flex h-2 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-700/40">
                                                @if($p['sold'])
                                                    <div class="bg-emerald-500" style="width: {{ $p['sold'] }}%"></div>
                                                @endif
                                                @if($p['active'])
                                                    <div class="bg-sky-500" style="width: {{ $p['active'] }}%"></div>
                                                @endif
                                                @if($p['pending'])
                                                    <div class="bg-amber-400" style="width: {{ $p['pending'] }}%"></div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="flex items-center gap-4 mt-4 text-[10px] font-bold">
                                    <span class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Sold</span>
                                    <span class="flex items-center gap-1 text-sky-600 dark:text-sky-400"><span class="w-2 h-2 rounded-full bg-sky-500"></span> Active</span>
                                    <span class="flex items-center gap-1 text-amber-600 dark:text-amber-400"><span class="w-2 h-2 rounded-full bg-amber-400"></span> Pending</span>
                                </div>
                            </div>

                            {{-- Lead Generation Donut (2/5) --}}
                            <div class="lg:col-span-2 rounded-xl bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700/40 p-4 shadow-sm">
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Lead Generation</h4>
                                <div class="flex items-center justify-center">
                                    <svg viewBox="0 0 120 120" class="w-28 h-28">
                                        {{-- Background ring --}}
                                        <circle cx="60" cy="60" r="48" stroke="#e2e8f0" stroke-width="14" fill="none" class="dark:stroke-slate-700"/>
                                        {{-- Direct 40% (emerald) --}}
                                        <circle cx="60" cy="60" r="48" stroke="#10b981" stroke-width="14" fill="none" stroke-dasharray="120.6 301.6" stroke-dashoffset="-0" transform="rotate(-90 60 60)" stroke-linecap="round"/>
                                        {{-- Referral 25% (amber) --}}
                                        <circle cx="60" cy="60" r="48" stroke="#d4a03c" stroke-width="14" fill="none" stroke-dasharray="75.4 301.6" stroke-dashoffset="-120.6" transform="rotate(-90 60 60)" stroke-linecap="round"/>
                                        {{-- Online 20% (sky) --}}
                                        <circle cx="60" cy="60" r="48" stroke="#0ea5e9" stroke-width="14" fill="none" stroke-dasharray="60.3 301.6" stroke-dashoffset="-196" transform="rotate(-90 60 60)" stroke-linecap="round"/>
                                        {{-- Walk-in 15% (slate) --}}
                                        <circle cx="60" cy="60" r="48" stroke="#94a3b8" stroke-width="14" fill="none" stroke-dasharray="45.2 301.6" stroke-dashoffset="-256.3" transform="rotate(-90 60 60)" stroke-linecap="round"/>
                                        {{-- Center text --}}
                                        <text x="60" y="56" text-anchor="middle" fill="#0f172a" style="font-size: 18px; font-weight: 900;" class="dark:hidden">89</text>
                                        <text x="60" y="56" text-anchor="middle" fill="#ffffff" style="font-size: 18px; font-weight: 900;" class="hidden dark:block">89</text>
                                        <text x="60" y="72" text-anchor="middle" fill="#94a3b8" style="font-size: 9px; font-weight: 600;">TOTAL LEADS</text>
                                    </svg>
                                </div>
                                <div class="grid grid-cols-2 gap-x-3 gap-y-1 mt-4 text-[10px] font-bold">
                                    <span class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Direct 40%</span>
                                    <span class="flex items-center gap-1 text-amber-600 dark:text-amber-400"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Referral 25%</span>
                                    <span class="flex items-center gap-1 text-sky-600 dark:text-sky-400"><span class="w-2 h-2 rounded-full bg-sky-500"></span> Online 20%</span>
                                    <span class="flex items-center gap-1 text-slate-500"><span class="w-2 h-2 rounded-full bg-slate-400"></span> Walk-in 15%</span>
                                </div>
                            </div>
                        </div>

                        {{-- Featured Listings --}}
                        <div class="rounded-xl bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700/40 p-4 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">Featured Listings</h4>
                                <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 cursor-pointer hover:underline">View All →</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                @php
                                    $listings = [
                                        ['title' => 'The Crown Villa',     'city' => 'Forbes Park, Makati',   'price' => '₱85M',   'badge' => 'For Sale',  'badgeColor' => 'emerald'],
                                        ['title' => 'Skyline Penthouse',   'city' => 'BGC, Taguig',           'price' => '₱120K/mo', 'badge' => 'For Rent',  'badgeColor' => 'sky'],
                                        ['title' => 'Azure Estate',        'city' => 'Mactan, Cebu',          'price' => '₱32M',   'badge' => 'For Sale',  'badgeColor' => 'emerald'],
                                    ];
                                @endphp
                                @foreach($listings as $l)
                                    <div class="rounded-lg border border-slate-200 dark:border-slate-700/40 overflow-hidden bg-[#FCFAF7] dark:bg-slate-900/40 hover:shadow-md transition-shadow">
                                        <div class="h-24 bg-gradient-to-br from-slate-200 to-slate-300 dark:from-slate-700 dark:to-slate-800 flex items-center justify-center">
                                            <x-heroicon-o-home-modern class="w-8 h-8 text-slate-400 dark:text-slate-500" />
                                        </div>
                                        <div class="p-3">
                                            <h5 class="text-xs font-bold text-slate-800 dark:text-white">{{ $l['title'] }}</h5>
                                            <p class="text-[10px] text-slate-400 flex items-center gap-0.5 mt-0.5">
                                                <x-heroicon-o-map-pin class="w-2.5 h-2.5" /> {{ $l['city'] }}
                                            </p>
                                            <div class="flex items-center justify-between mt-2">
                                                <span class="text-sm font-black text-amber-700 dark:text-amber-400">{{ $l['price'] }}</span>
                                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-{{ $l['badgeColor'] }}-100 dark:bg-{{ $l['badgeColor'] }}-500/10 text-{{ $l['badgeColor'] }}-700 dark:text-{{ $l['badgeColor'] }}-400">{{ $l['badge'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Caption under dashboard --}}
            <div class="text-center mt-8">
                <p class="text-sm font-bold text-slate-900 dark:text-white">Agent Dashboard — Real Estate</p>
                <p class="text-xs text-slate-500 dark:text-slate-500 mt-1">Portfolio tracking, lead intelligence, and property performance analytics — built for brokers and agents.</p>
                <a href="{{ route('register.sector') }}" class="inline-flex items-center gap-1.5 mt-4 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-lg shadow-emerald-600/20 transition-all duration-300">
                    List Properties <x-heroicon-o-arrow-right class="w-4 h-4" />
                </a>
            </div>
        </div>

        {{-- Sector cards below --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 max-w-7xl mx-auto mt-16">
            @php
                $sectorCounts = \App\Models\Store::query()
                    ->where('status', \App\StoreStatus::Approved)
                    ->whereNotNull('sector')
                    ->selectRaw('sector, count(*) as total')
                    ->groupBy('sector')
                    ->pluck('total', 'sector');

                $sectors = \App\Models\Sector::active()->get();
            @endphp
            @foreach ($sectors as $sector)
                @php $count = $sectorCounts[$sector->slug] ?? 0; @endphp
                <a href="{{ route('sector.browse', ['search' => $sector->name]) }}"
                   class="group relative flex flex-col p-5 rounded-2xl bg-white dark:bg-slate-800/40 backdrop-blur-sm border border-slate-200 dark:border-slate-700/50 hover:border-{{ $sector->color }}-300 dark:hover:border-{{ $sector->color }}-500/50 hover:shadow-xl hover:shadow-{{ $sector->color }}-500/5 dark:hover:shadow-2xl dark:hover:shadow-{{ $sector->color }}-500/10 transition-all duration-500 transform hover:-translate-y-1 overflow-hidden h-full">

                    {{-- Ambient hover glow --}}
                    <div class="absolute inset-0 bg-gradient-to-br from-{{ $sector->color }}-500/0 to-{{ $sector->color }}-500/0 group-hover:from-{{ $sector->color }}-500/5 group-hover:to-transparent dark:group-hover:from-{{ $sector->color }}-500/10 transition-all duration-500 pointer-events-none"></div>

                    {{-- Accent bar --}}
                    <div class="absolute top-0 inset-x-0 h-[3px] bg-gradient-to-r from-transparent via-{{ $sector->color }}-400 to-transparent scale-x-0 group-hover:scale-x-100 transition-transform duration-500 ease-out origin-left opacity-70"></div>

                    <div class="relative z-10 flex flex-col h-full">
                        <div class="flex items-start justify-between mb-4">
                            <div class="h-11 w-11 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 group-hover:bg-{{ $sector->color }}-50 dark:group-hover:bg-{{ $sector->color }}-500/20 group-hover:border-{{ $sector->color }}-200 dark:group-hover:border-{{ $sector->color }}-500/30 flex items-center justify-center transition-all duration-300">
                                <x-dynamic-component :component="$sector->icon" class="w-5 h-5 text-slate-500 dark:text-slate-400 group-hover:text-{{ $sector->color }}-600 dark:group-hover:text-{{ $sector->color }}-400 transition-colors duration-300" />
                            </div>
                            <span class="inline-flex items-center px-2 py-1 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-[10px] font-bold text-slate-500 dark:text-slate-400 tracking-wider">
                                {{ $count }} <span class="hidden sm:inline ml-1">STORES</span>
                            </span>
                        </div>

                        <h3 class="text-base font-bold text-slate-900 dark:text-white group-hover:text-{{ $sector->color }}-700 dark:group-hover:text-{{ $sector->color }}-300 transition-colors duration-300 mb-1">{{ $sector->name }}</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mt-auto">{{ Str::limit($sector->description, 90) }}</p>

                        <div class="mt-4 flex items-center text-xs font-semibold text-slate-400 dark:text-slate-500 group-hover:text-{{ $sector->color }}-600 dark:group-hover:text-{{ $sector->color }}-400 transition-colors duration-300">
                            Explore sector
                            <x-heroicon-o-arrow-right class="w-3.5 h-3.5 ml-1.5 transform group-hover:translate-x-1 transition-transform" />
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- CTA --}}
        <div class="mt-14 text-center">
            <a href="{{ route('sector.browse') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-bold rounded-xl text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 backdrop-blur-sm border border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-500 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm transition-all duration-300">
                View Enterprise Directory
                <x-heroicon-o-arrow-right class="w-4 h-4" />
            </a>
        </div>
    </div>
</div>
