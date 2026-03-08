{{-- Premium Supplier Card — clean, modern procurement style --}}
@props(['store'])

@php
    $sectorSlug = $store->sector;
    $sectorColors = [
        'ecommerce' => 'emerald',
        'real_estate' => 'violet',
    ];
    $color = $sectorColors[$sectorSlug] ?? 'sky';
    $sectorName = ucwords(str_replace('_', ' ', $sectorSlug ?? 'General'));
    $city = $store->address['city'] ?? null;
    $province = $store->address['province'] ?? null;
    $location = $city ?: ($province ?: 'Philippines');
    $hasPermit = (bool) $store->business_permit;
    $hasId = (bool) $store->id_type;
@endphp

<a href="{{ route('suppliers.show', $store->slug) }}" class="block h-full" id="supplier-card-{{ $store->id }}">
    <div class="h-full flex flex-col">

        {{-- Colored header strip with sector + badge --}}
        <div class="relative px-5 pt-5 pb-4">
            {{-- Subtle gradient background glow --}}
            <div class="absolute inset-0 bg-gradient-to-br from-{{ $color }}-500/[0.06] to-transparent dark:from-{{ $color }}-500/[0.12] rounded-t-2xl"></div>

            <div class="relative flex items-start gap-4">
                {{-- Avatar --}}
                <div class="shrink-0 h-14 w-14 rounded-2xl bg-gradient-to-br from-{{ $color }}-500 to-{{ $color }}-600 flex items-center justify-center shadow-lg shadow-{{ $color }}-500/20 ring-2 ring-white dark:ring-slate-800 overflow-hidden">
                    @if ($store->logo)
                        <img src="{{ $store->logo }}" alt="{{ $store->name }}" class="h-full w-full object-cover">
                    @else
                        <span class="text-base font-black text-white/90 tracking-tight">{{ strtoupper(substr($store->name, 0, 2)) }}</span>
                    @endif
                </div>

                {{-- Name + sector label --}}
                <div class="flex-1 min-w-0 pt-0.5">
                    <h3 class="text-[15px] font-extrabold text-slate-900 dark:text-white truncate leading-snug group-hover:text-{{ $color }}-600 dark:group-hover:text-{{ $color }}-400 transition-colors duration-200">{{ $store->name }}</h3>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-{{ $color }}-50 dark:bg-{{ $color }}-500/10 border border-{{ $color }}-100 dark:border-{{ $color }}-500/20 text-[10px] font-bold text-{{ $color }}-700 dark:text-{{ $color }}-400 uppercase tracking-wider">
                            {{ $sectorName }}
                        </span>
                        @if ($hasPermit)
                            <span class="inline-flex items-center gap-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
                                <x-heroicon-s-check-badge class="w-3.5 h-3.5" />
                                Verified
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Description --}}
        <div class="px-5 flex-1">
            <p class="text-[13px] text-slate-500 dark:text-slate-400 leading-relaxed line-clamp-2">{{ $store->description ?? 'Verified store on NegosyoHub. Browse their profile for products and details.' }}</p>
        </div>

        {{-- Meta row --}}
        <div class="px-5 pt-4 pb-5">
            {{-- Info chips --}}
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2 text-xs text-slate-400 dark:text-slate-500 mb-4">
                <span class="inline-flex items-center gap-1.5">
                    <x-heroicon-o-map-pin class="w-3.5 h-3.5 text-{{ $color }}-400 dark:text-{{ $color }}-500" />
                    <span class="font-medium text-slate-600 dark:text-slate-300">{{ $location }}</span>
                </span>
                <span class="text-slate-200 dark:text-slate-700">&middot;</span>
                <span class="inline-flex items-center gap-1.5">
                    <x-heroicon-o-calendar-days class="w-3.5 h-3.5 text-slate-300 dark:text-slate-600" />
                    {{ $store->created_at->format('M Y') }}
                </span>
                @if ($hasId)
                    <span class="text-slate-200 dark:text-slate-700">&middot;</span>
                    <span class="inline-flex items-center gap-1 text-sky-500 dark:text-sky-400 font-semibold">
                        <x-heroicon-o-document-check class="w-3.5 h-3.5" />
                        SEC/DTI
                    </span>
                @endif
            </div>

            {{-- CTA --}}
            <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-700/40">
                <span class="text-xs font-bold text-{{ $color }}-600 dark:text-{{ $color }}-400 group-hover:translate-x-0.5 transition-transform duration-200 flex items-center gap-1.5">
                    View Profile
                    <x-heroicon-o-arrow-right class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform duration-200" />
                </span>
                <div class="flex items-center gap-1.5">
                    @if ($hasPermit)
                        <div class="h-6 w-6 rounded-full bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center" title="Business Permit Verified">
                            <x-heroicon-o-shield-check class="w-3.5 h-3.5 text-emerald-500" />
                        </div>
                    @endif
                    @if ($hasId)
                        <div class="h-6 w-6 rounded-full bg-sky-50 dark:bg-sky-500/10 flex items-center justify-center" title="SEC/DTI Registered">
                            <x-heroicon-o-identification class="w-3.5 h-3.5 text-sky-500" />
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</a>
