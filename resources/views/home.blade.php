@extends('layouts.vizzbud')

@section('title', 'Vizzbud | Real-Time Dive Conditions, Logs & Stats')
@section('meta_description', 'Explore live scuba dive site conditions and log your underwater adventures with Vizzbud.')

@section('content')

@php
  // Helpers for status chip style
  $status = optional($featured?->latestCondition)->status;
  $chip = match($status) {
      'green'  => 'bg-emerald-500/15 text-emerald-300 ring-emerald-500/30',
      'yellow' => 'bg-amber-500/15 text-amber-300 ring-amber-500/30',
      default  => 'bg-rose-500/15 text-rose-300 ring-rose-500/30',
  };
@endphp

<section class="relative">
  {{-- Subtle backdrop accent --}}
  <div class="pointer-events-none absolute inset-x-0 -top-24 h-48 bg-gradient-to-b from-cyan-500/10 to-transparent blur-2xl"></div>

  <div class="mx-auto max-w-7xl px-6 pt-12 pb-16">
    <header class="mb-8">
      <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Plan your next dive</h1>
      <p class="mt-2 text-white/70">Featured site and quick actions.</p>
    </header>

    {{-- Three modules: Featured (primary), Map, Log --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      {{-- Featured Dive Site — whole card clickable, fixed-matched column heights --}}
      @if($featured)
        @php
          $c = $featured->latestCondition;
          $status = $c->status ?? null;
          $chip = match($status) {
            'green'  => 'bg-emerald-500/15 text-emerald-300 ring-emerald-500/30',
            'yellow' => 'bg-amber-500/15 text-amber-300 ring-amber-500/30',
            default  => 'bg-rose-500/15 text-rose-300 ring-rose-500/30',
          };
        @endphp

        <a href="{{ route('dive-sites.show', $featured) }}"
          aria-label="View {{ $featured->name }}"
          class="lg:col-span-2 group relative block overflow-hidden rounded-2xl border border-white/10 bg-slate-900/60 ring-1 ring-white/10 shadow-xl hover:scale-[1.01] transition">

          {{-- subtle glow --}}
          <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(34,211,238,0.10),transparent_60%)] pointer-events-none"></div>

          <div class="relative p-6 sm:p-8">
            <div class="mb-4 flex items-center justify-between gap-4">
              <h2 class="text-xl sm:text-2xl font-semibold">Featured Dive Site</h2>

              {{-- status row --}}
              <div class="flex items-center gap-2">
                <span class="text-xs sm:text-sm font-medium text-white/70">
                  Current Diveability:
                </span>
                <span class="rounded-full px-2.5 py-1 text-xs sm:text-sm font-semibold tabular-nums ring-1 {{ $chip }}">
                  {{ strtoupper($status ?? 'N/A') }}
                </span>
              </div>
            </div>

            {{-- Equal-height two-column layout --}}
            <div class="grid gap-6 md:grid-cols-2 items-stretch">

              {{-- Left: image with fixed/consistent height --}}
              <div class="relative overflow-hidden rounded-xl border border-white/10 md:min-h-[14rem]">
                <div class="absolute inset-0 bg-cover bg-center transition group-hover:scale-[1.02]"
                    style="background-image:url('{{ asset('images/main/turtle.webp') }}')"></div>
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-900/70 to-transparent p-3">
                  <div class="text-xs text-white/80">Representative image</div>
                </div>
              </div>

              {{-- Right: details, locked to same height --}}
              <div class="md:min-h-[14rem] flex flex-col">
                <div>
                  <h3 class="text-lg font-semibold text-cyan-300">{{ $featured->name }}</h3>
                  <p class="mt-2 text-sm text-white/80 leading-relaxed">
                    {{ $featured->description ?: 'No description available.' }}
                  </p>
                </div>

                {{-- Key metrics (big, scannable) --}}
                <div class="mt-5 grid grid-cols-3 gap-3">
                  <div class="rounded-xl border border-white/10 bg-white/5 p-3 text-center">
                    <div class="text-[0.7rem] uppercase tracking-wide text-white/60">Swell</div>
                    <div class="mt-1 text-2xl font-bold tabular-nums">
                      {{ $c?->wave_height ? number_format($c->wave_height,1) : '–' }}
                      <span class="text-sm font-medium align-top">m</span>
                    </div>
                  </div>
                  <div class="rounded-xl border border-white/10 bg-white/5 p-3 text-center">
                    <div class="text-[0.7rem] uppercase tracking-wide text-white/60">Wind</div>
                    <div class="mt-1 text-2xl font-bold tabular-nums">
                      {{ $c?->wind_speed ? number_format($c->wind_speed * 1.94384,0) : '–' }}
                      <span class="text-sm font-medium align-top">kt</span>
                    </div>
                  </div>
                  <div class="rounded-xl border border-white/10 bg-white/5 p-3 text-center">
                    <div class="text-[0.7rem] uppercase tracking-wide text-white/60">Water</div>
                    <div class="mt-1 text-2xl font-bold tabular-nums">
                      {{ $c?->water_temperature ? number_format($c->water_temperature,1) : '–' }}
                      <span class="text-sm font-medium align-top">°C</span>
                    </div>
                  </div>
                </div>

                {{-- inline cue like other boxes; whole card is clickable --}}
                <span class="mt-5 inline-block text-cyan-400 relative
                            after:absolute after:-bottom-1 after:left-0 after:h-0.5 after:w-0
                            after:bg-cyan-400 after:transition-all after:duration-300 group-hover:after:w-1/2">
                  View site
                </span>
              </div>
            </div>
          </div>
        </a>
      @else
        <div class="lg:col-span-2 relative overflow-hidden rounded-2xl border border-white/10 bg-slate-900/60 ring-1 ring-white/10 shadow-xl">
          <div class="relative p-6 sm:p-8">
            <h2 class="text-xl sm:text-2xl font-semibold">Featured Dive Site</h2>
            <p class="mt-4 text-white/70">No featured site yet. Add a site or run the conditions fetch.</p>
          </div>
        </div>
      @endif

      {{-- Quick Action: Dive Map (compact bottom overlay) --}}
      <a href="{{ route('dive-sites.index') }}"
        class="group relative overflow-hidden rounded-2xl border border-white/10 bg-slate-900/60 ring-1 ring-white/10 shadow-xl hover:scale-[1.01] transition">

        <!-- Background image -->
        <div class="absolute inset-0 bg-cover bg-center transition group-hover:scale-105"
            style="background-image:url('{{ asset('images/main/divemap.webp') }}')"></div>

        <!-- Compact bottom overlay -->
        <div class="absolute inset-x-0 bottom-0 bg-slate-900/85 backdrop-blur-sm px-4 py-3 sm:px-5 sm:py-4">
          <h3 class="text-lg sm:text-xl font-semibold text-white">Dive Map</h3>
          <p class="mt-1 text-xs sm:text-sm text-slate-300">Browse sites and latest conditions.</p>
          <span class="mt-2 inline-block text-cyan-400 relative text-sm
                      after:absolute after:-bottom-1 after:left-0 after:h-0.5 after:w-0
                      after:bg-cyan-400 after:transition-all after:duration-300 group-hover:after:w-full">
            Open map
          </span>
        </div>
      </a>

      {{-- Quick Action: Dive Log --}}
      <a href="{{ route('logbook.index') }}"
         class="group relative overflow-hidden rounded-2xl border border-white/10 bg-slate-900/60 ring-1 ring-white/10 shadow-xl hover:scale-[1.01] transition">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(34,211,238,0.10),transparent_60%)]"></div>
        <div class="relative p-6 sm:p-8">
          <h3 class="mt-2 text-2xl font-semibold">Dive Log</h3>
          <p class="mt-2 text-sm text-white/70">View stats and past dives in your logbook.</p>
          <span class="mt-6 inline-block text-cyan-400 relative
                       after:absolute after:-bottom-1 after:left-0 after:h-0.5 after:w-0 after:bg-cyan-400 after:transition-all after:duration-300 group-hover:after:w-full">
            Open logbook
          </span>
        </div>
      </a>

    </div>
  </div>
</section>

@endsection