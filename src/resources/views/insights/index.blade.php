<x-layouts.app :title="'Market Insights — NegosyoHub Marketplace'">

    {{-- Premium Hero Section --}}
    <div class="relative bg-white dark:bg-[#0B1120] border-b border-slate-200 dark:border-slate-800/60 overflow-hidden" id="insights-hero">

        {{-- Decorative Background Gradients --}}
        <div class="absolute inset-0 pointer-events-none overflow-hidden z-0 hidden dark:block">
            <div class="absolute -top-1/4 right-1/4 w-[600px] h-[600px] bg-violet-500/10 rounded-full blur-[120px] mix-blend-screen opacity-50"></div>
            <div class="absolute bottom-0 left-1/4 w-[500px] h-[500px] bg-sky-500/10 rounded-full blur-[100px] mix-blend-screen opacity-40"></div>
        </div>

        {{-- Background Dot Pattern & Grid --}}
        <div class="absolute inset-0 z-0 opacity-[0.03] dark:opacity-[0.05] bg-[linear-gradient(to_right,#80808012_1px,transparent_1px),linear-gradient(to_bottom,#80808012_1px,transparent_1px)] bg-[size:24px_24px]"></div>

        <div class="relative z-10 max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
            <div class="max-w-2xl text-center md:text-left mx-auto md:mx-0">
                <nav class="flex items-center justify-center md:justify-start gap-2 text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-6">
                    <a href="{{ route('home') }}" class="hover:text-violet-500 dark:hover:text-violet-400 transition-colors">Home</a>
                    <span class="text-slate-300 dark:text-slate-600">/</span>
                    <span class="text-slate-800 dark:text-slate-200">Market Insights</span>
                </nav>

                <h1 class="text-4xl sm:text-5xl font-black text-slate-900 dark:text-white tracking-tight leading-[1.15]">
                    Enterprise <span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-500 to-sky-500">Analytics</span>
                </h1>

                <p class="mt-4 text-base sm:text-lg text-slate-600 dark:text-slate-400 leading-relaxed max-w-xl mx-auto md:mx-0">
                    Data-driven intelligence on Philippine marketplace trends. Analyze industry performance, monitor regional activity, and leverage marketplace data to grow your business.
                </p>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="bg-slate-50/50 dark:bg-[#060A13]">
        <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-14 lg:py-20">

            {{-- Quick Stats Matrix --}}
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-16">
                @php
                    $approvedCount = \App\Models\Store::where('status', \App\StoreStatus::Approved)->count();
                    $userCount = \App\Models\User::count();
                    $sectorCount = \App\Models\Sector::active()->count();
                    $regionCount = \App\Models\Store::query()
                        ->where('status', \App\StoreStatus::Approved)
                        ->whereNotNull('address')
                        ->selectRaw("distinct address->>'city'")
                        ->count();

                    $insightStats = [
                        ['label' => 'Verified Suppliers', 'value' => number_format($approvedCount), 'change' => 'Active', 'up' => true, 'icon' => 'heroicon-o-building-storefront', 'color' => 'sky'],
                        ['label' => 'Registered Users', 'value' => number_format($userCount), 'change' => 'Growing', 'up' => true, 'icon' => 'heroicon-o-users', 'color' => 'emerald'],
                        ['label' => 'Market Sectors', 'value' => $sectorCount, 'change' => 'Expanding', 'up' => true, 'icon' => 'heroicon-o-squares-2x2', 'color' => 'amber'],
                        ['label' => 'Cities Covered', 'value' => $regionCount, 'change' => 'Nationwide', 'up' => true, 'icon' => 'heroicon-o-map-pin', 'color' => 'violet'],
                    ];
                @endphp
                @foreach ($insightStats as $stat)
                    <div class="group bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700/50 rounded-3xl p-8 hover:shadow-xl hover:-translate-y-1 transition-all duration-500 relative overflow-hidden">
                        {{-- Hover glow --}}
                        <div class="absolute inset-0 bg-gradient-to-br from-{{ $stat['color'] }}-500/0 to-transparent group-hover:from-{{ $stat['color'] }}-500/5 transition-colors duration-500"></div>

                        <div class="relative z-10">
                            <div class="flex items-center justify-between mb-6">
                                <div class="h-12 w-12 rounded-2xl bg-{{ $stat['color'] }}-50 dark:bg-{{ $stat['color'] }}-500/10 border border-{{ $stat['color'] }}-100 dark:border-{{ $stat['color'] }}-500/20 flex items-center justify-center group-hover:scale-110 transition-transform duration-500">
                                    <x-dynamic-component :component="$stat['icon']" class="w-6 h-6 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400" />
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 text-[10px] font-bold uppercase tracking-wider {{ $stat['up'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                    @if ($stat['up'])
                                        <x-heroicon-s-arrow-trending-up class="w-3 h-3" />
                                    @endif
                                    {{ $stat['change'] }}
                                </span>
                            </div>
                            <p class="text-3xl font-black text-slate-900 dark:text-white tabular-nums tracking-tight mb-1">{{ $stat['value'] }}</p>
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-widest">{{ $stat['label'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Deep Dive Analytics Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-16">

                {{-- Top Industries (Takes up 2 columns) --}}
                <div class="lg:col-span-2 bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700/50 rounded-3xl p-8 shadow-sm relative overflow-hidden">
                    <div class="absolute -right-40 -top-40 w-80 h-80 bg-sky-500/5 rounded-full blur-[80px]"></div>

                    <div class="flex items-center justify-between mb-8 relative z-10">
                        <div>
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">Sector Dominance Matrix</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Real-time breakdown of top performing industries by supply volume.</p>
                        </div>
                        <div class="hidden sm:flex items-center gap-2 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800">
                            <span class="flex h-2 w-2 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-sky-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-sky-500"></span>
                            </span>
                            Live Data
                        </div>
                    </div>

                    <div class="space-y-6 relative z-10">
                        @php
                            // Real data: count stores per sector
                            $sectorCounts = \App\Models\Store::query()
                                ->where('status', \App\StoreStatus::Approved)
                                ->whereNotNull('sector')
                                ->selectRaw('sector, count(*) as total')
                                ->groupBy('sector')
                                ->orderByDesc('total')
                                ->limit(5)
                                ->get();

                            $maxCount = $sectorCounts->max('total') ?: 1;
                            $barColors = [
                                ['color' => 'bg-emerald-500', 'from' => 'from-emerald-400', 'to' => 'to-emerald-600', 'text' => 'emerald'],
                                ['color' => 'bg-sky-500', 'from' => 'from-sky-400', 'to' => 'to-sky-600', 'text' => 'sky'],
                                ['color' => 'bg-amber-500', 'from' => 'from-amber-400', 'to' => 'to-amber-600', 'text' => 'amber'],
                                ['color' => 'bg-rose-500', 'from' => 'from-rose-400', 'to' => 'to-rose-600', 'text' => 'rose'],
                                ['color' => 'bg-violet-500', 'from' => 'from-violet-400', 'to' => 'to-violet-600', 'text' => 'violet'],
                            ];

                            $activeSectors = \App\Models\Sector::active()->pluck('name', 'slug');
                        @endphp
                        @forelse ($sectorCounts as $idx => $sectorData)
                            @php
                                $barWidth = round(($sectorData->total / $maxCount) * 100);
                                $colors = $barColors[$idx % count($barColors)];
                                $sectorSlug = (string) $sectorData->sector;
                                $sectorName = $activeSectors[$sectorSlug] ?? ucwords(str_replace('_', ' ', $sectorSlug));
                            @endphp
                            <div class="group">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-3">
                                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 tabular-nums w-4">{{ str_pad($idx + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                        <span class="text-sm font-bold text-slate-800 dark:text-slate-200 group-hover:text-{{ $colors['text'] }}-500 transition-colors">{{ $sectorName }}</span>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 tabular-nums">{{ $sectorData->total }} {{ Str::plural('store', $sectorData->total) }}</span>
                                    </div>
                                </div>
                                <div class="h-2 w-full bg-slate-100 dark:bg-slate-900 rounded-full overflow-hidden border border-slate-200 dark:border-slate-800">
                                    <div class="h-full bg-gradient-to-r {{ $colors['from'] }} {{ $colors['to'] }} rounded-full relative overflow-hidden group-hover:scale-x-[1.02] transition-transform origin-left" style="width: {{ $barWidth }}%">
                                        <div class="absolute top-0 bottom-0 left-0 w-1/2 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-[shimmer_2s_infinite]"></div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <p class="text-sm text-slate-500 dark:text-slate-400">No sector data available yet.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Side Analytics Stack --}}
                <div class="flex flex-col gap-8">
                    {{-- Platform Health --}}
                    <div class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700/50 rounded-3xl p-8 shadow-sm flex-1">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="h-10 w-10 rounded-xl bg-violet-50 dark:bg-violet-500/10 border border-violet-100 dark:border-violet-500/20 flex items-center justify-center">
                                <x-heroicon-o-shield-check class="w-5 h-5 text-violet-600 dark:text-violet-400" />
                            </div>
                            <h3 class="text-lg font-extrabold text-slate-900 dark:text-white tracking-tight">System Integrity</h3>
                        </div>

                        <div class="space-y-6">
                            @php
                                $totalApproved = \App\Models\Store::where('status', \App\StoreStatus::Approved)->count();
                                $withPermit = \App\Models\Store::where('status', \App\StoreStatus::Approved)->whereNotNull('business_permit')->count();
                                $complianceRate = $totalApproved > 0 ? round(($withPermit / $totalApproved) * 100) : 0;

                                $health = [
                                    ['label' => 'Permit Compliance', 'value' => $complianceRate.'%', 'color' => $complianceRate >= 80 ? 'text-emerald-500' : 'text-amber-500'],
                                    ['label' => 'Active Sectors', 'value' => \App\Models\Sector::active()->count(), 'color' => 'text-sky-500'],
                                    ['label' => 'Platform Status', 'value' => 'Online', 'color' => 'text-emerald-500'],
                                ];
                            @endphp
                            @foreach ($health as $h)
                                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700/50 pb-4 last:border-0 last:pb-0">
                                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ $h['label'] }}</span>
                                    <span class="text-lg font-black {{ $h['color'] }} tabular-nums">{{ $h['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Regional Heatmap Text --}}
                    <div class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700/50 rounded-3xl p-8 shadow-sm flex-1 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-br from-transparent to-amber-500/5 mix-blend-overlay"></div>
                        <h3 class="text-lg font-extrabold text-slate-900 dark:text-white tracking-tight mb-5">Geo-distribution Top 3</h3>

                        @php
                            // Real data: count stores per city/region from address JSON
                            $totalStores = \App\Models\Store::where('status', \App\StoreStatus::Approved)->count() ?: 1;
                            $topRegions = \App\Models\Store::query()
                                ->where('status', \App\StoreStatus::Approved)
                                ->whereNotNull('address')
                                ->selectRaw("address->>'city' as city, count(*) as total")
                                ->groupByRaw("address->>'city'")
                                ->orderByDesc('total')
                                ->limit(3)
                                ->get();

                            $geoColors = ['sky', 'emerald', 'amber'];
                        @endphp
                        <div class="space-y-4">
                            @forelse ($topRegions as $idx => $region)
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-1.5 h-1.5 rounded-full bg-{{ $geoColors[$idx] ?? 'slate' }}-500"></div>
                                        <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $region->city ?? 'Unknown' }}</span>
                                    </div>
                                    <span class="text-xs font-black text-slate-900 dark:text-white bg-slate-100 dark:bg-slate-900 px-2.5 py-1 rounded-md border border-slate-200 dark:border-slate-800">{{ number_format(($region->total / $totalStores) * 100, 1) }}%</span>
                                </div>
                            @empty
                                <p class="text-sm text-slate-500 dark:text-slate-400">No regional data available yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>

            {{-- Post-content Disclaimer --}}
            <div class="flex items-center justify-center gap-2 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                <x-heroicon-o-information-circle class="w-4 h-4" />
                <span>Analytics Engine v1.0 — Data updates every 24 hours</span>
            </div>
        </div>
    </div>

</x-layouts.app>
