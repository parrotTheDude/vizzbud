@extends('layouts.vizzbud')

@section('title', 'Real-Time Dive Conditions, Site Map & More | Vizzbud')
@section('meta_description', 'Explore live scuba dive site conditions and log your underwater adventures with Vizzbud. Plan your dives smarter with real-time updates.')

@section('head')
  {{-- Structured data for Google --}}
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "WebSite",
    "name": "Vizzbud",
    "url": "https://vizzbud.com/",
    "potentialAction": {
      "@type": "SearchAction",
      "target": "https://vizzbud.com/dive-sites?query={search_term_string}",
      "query-input": "required name=search_term_string"
    }
  }
  </script>

  {{-- Open Graph / Twitter meta tags --}}
  <meta property="og:type" content="website">
  <meta property="og:title" content="Vizzbud | Real-Time Dive Conditions, Logs & Stats">
  <meta property="og:description" content="Explore live scuba dive site conditions and log your underwater adventures with Vizzbud.">
  <meta property="og:image" content="{{ asset('og-image.webp') }}">
  <meta property="og:url" content="https://vizzbud.com/">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Vizzbud | Real-Time Dive Conditions, Logs & Stats">
  <meta name="twitter:description" content="Explore live scuba dive site conditions and log your underwater adventures with Vizzbud.">
  <meta name="twitter:image" content="{{ asset('og-image.webp') }}">

  {{-- Preload key visual assets for LCP --}}
  <link rel="preload" as="image" href="{{ asset('vizzbudLogo.webp') }}">
  @if(!empty($featured) && $featured->photos()->where('is_featured', true)->exists())
    <link rel="preload" as="image" href="{{ asset($featured->photos()->where('is_featured', true)->first()->image_path) }}" fetchpriority="high">
  @endif
@endsection

@section('content')

@php
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

      {{-- Featured Dive Site — title/status → image → details (mobile), side-by-side on desktop --}}
      @if($featured)
        @php
          $c = $featured->latestCondition;
          $status = $c->status ?? null;
          $chip = match($status) {
            'green'  => 'bg-emerald-500/15 text-emerald-300 ring-emerald-500/30',
            'yellow' => 'bg-amber-500/15 text-amber-300 ring-amber-500/30',
            default  => 'bg-rose-500/15 text-rose-300 ring-rose-500/30',
          };
          $featuredPhoto = $featured->photos()->where('is_featured', true)->first();
          $heroImage = $featuredPhoto
              ? asset($featuredPhoto->image_path)
              : asset('images/divesites/default-home.webp');
        @endphp

        <a href="{{ route('dive-sites.show', $featured) }}"
          aria-label="View {{ $featured->name }}"
          class="lg:col-span-2 group relative block overflow-hidden rounded-2xl border border-white/10 bg-slate-900/60 ring-1 ring-white/10 shadow-xl hover:scale-[1.01] transition">

          {{-- subtle glow --}}
          <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(34,211,238,0.10),transparent_60%)]"></div>

          <div class="relative p-6 sm:p-8">
            {{-- Header (stacks on mobile, row on ≥sm) --}}
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
              <h2 class="text-xl sm:text-2xl font-semibold leading-tight">
                Featured Dive Site
              </h2>

              {{-- Status block --}}
              <div class="flex items-center gap-2 sm:gap-3">
                <span class="text-xs sm:text-sm font-medium text-white/70">
                  Current Dive Status:
                </span>
                <span class="rounded-full px-2.5 py-1 text-xs sm:text-sm font-semibold tabular-nums ring-1 {{ $chip }}">
                  {{ strtoupper($status ?? 'N/A') }}
                </span>
              </div>
            </div>

            {{-- Body: stack on mobile, side-by-side on md+ --}}
            <div class="grid gap-6 md:grid-cols-2 items-stretch">
              {{-- Image block: guaranteed height on mobile --}}
              @php
                $featuredPhoto = $featured->photos()->where('is_featured', true)->first();
                $heroImage = $featuredPhoto 
                    ? asset($featuredPhoto->image_path) 
                    : asset('images/divesites/default-home.webp');
              @endphp

              {{-- Optimized image with preload + alt + fetchpriority --}}
              <div class="relative overflow-hidden rounded-xl border border-white/10 md:order-1">
                <img
                  src="{{ $heroImage }}"
                  alt="Featured dive site: {{ $featured->name }}"
                  width="600" height="400"
                  class="object-cover w-full h-full transition group-hover:scale-[1.02]"
                  loading="eager"
                  fetchpriority="high"
                  decoding="async"
                >
              </div>

              {{-- Details block --}}
              <div class="md:order-2 flex flex-col md:min-h-[14rem]">
                <div>
                  <h3 class="text-lg font-semibold text-cyan-300">{{ $featured->name }}</h3>
                  <p class="mt-2 text-sm text-white/80 leading-relaxed">
                    {{ $featured->description ?: 'No description available.' }}
                  </p>
                </div>

                {{-- Conditions pills (compact, glassy) --}}
                <div class="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-2">
                  {{-- Swell --}}
                  <span class="flex items-center justify-center gap-1.5
                              rounded-full px-3 py-1.5 text-[11px] font-medium
                              bg-white/10 backdrop-blur-md ring-1 ring-white/15 border border-white/10 text-slate-200">
                    <img src="/icons/wave.svg" class="w-3.5 h-3.5 invert" alt="Swell">
                    <span class="uppercase tracking-wide text-white/70">Swell</span>
                    <span class="tabular-nums">
                      {{ $c?->wave_height ? number_format($c->wave_height,1) : '–' }}<span class="ml-0.5">m</span>
                    </span>
                  </span>

                  {{-- Wind --}}
                  <span class="flex items-center justify-center gap-1.5
                              rounded-full px-3 py-1.5 text-[11px] font-medium
                              bg-white/10 backdrop-blur-md ring-1 ring-white/15 border border-white/10 text-slate-200">
                    <img src="/icons/wind.svg" class="w-3.5 h-3.5 invert" alt="Wind">
                    <span class="uppercase tracking-wide text-white/70">Wind</span>
                    <span class="tabular-nums">
                      {{ $c?->wind_speed ? number_format($c->wind_speed * 1.94384, 0) : '–' }}<span class="ml-0.5">kt</span>
                    </span>
                  </span>

                  {{-- Water Temp --}}
                  <span class="flex items-center justify-center gap-1.5
                              rounded-full px-3 py-1.5 text-[11px] font-medium
                              bg-white/10 backdrop-blur-md ring-1 ring-white/15 border border-white/10 text-slate-200">
                    <img src="/icons/temperature.svg" class="w-3.5 h-3.5 invert" alt="Water Temp">
                    <span class="uppercase tracking-wide text-white/70">Water</span>
                    <span class="tabular-nums">
                      {{ $c?->water_temperature ? number_format($c->water_temperature,1) : '–' }}<span class="ml-0.5">°C</span>
                    </span>
                  </span>
                </div>

                {{-- inline cue --}}
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

      {{-- Quick Action: Dive Map (optimized for LCP) --}}
      <a href="{{ route('dive-sites.index') }}"
        class="group relative overflow-hidden rounded-2xl border border-white/10 bg-slate-900/60 ring-1 ring-white/10 shadow-xl hover:scale-[1.01] transition
                min-h-[220px] sm:min-h-[260px]">

        <img
          src="{{ asset('images/main/satellite.webp') }}"
          alt="Dive map preview background"
          class="absolute inset-0 w-full h-full object-cover transition group-hover:scale-105"
          fetchpriority="high"
          decoding="async">

        <!-- Compact bottom overlay -->
        <div class="absolute inset-x-0 bottom-0 bg-slate-900/85 backdrop-blur-sm px-4 py-3 sm:px-5 sm:py-4">
          <h3 class="text-lg sm:text-xl font-semibold text-white">Dive Sites</h3>
          <p class="mt-1 text-xs sm:text-sm text-slate-300">Browse sites and latest conditions.</p>
          <span class="mt-2 inline-block text-cyan-400 relative text-sm
                      after:absolute after:-bottom-1 after:left-0 after:h-0.5 after:w-0
                      after:bg-cyan-400 after:transition-all after:duration-300 group-hover:after:w-full">
            See Dive Sites
          </span>
        </div>
      </a>

      {{-- Dive Log --}}
      <a href="{{ route('logbook.index') }}"
        class="group relative overflow-hidden rounded-2xl border border-white/10 bg-slate-900/60
              ring-1 ring-white/10 shadow-xl hover:scale-[1.01] transition">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(34,211,238,0.10),transparent_60%)]"></div>
        <div class="relative p-6 sm:p-8">
          <h3 class="mt-2 text-2xl font-semibold">Dive Log</h3>
          <p class="mt-2 text-sm text-white/70">View stats and past dives in your logbook.</p>
          <span class="mt-6 inline-block text-cyan-400 relative
                      after:absolute after:-bottom-1 after:left-0 after:h-0.5 after:w-0
                      after:bg-cyan-400 after:transition-all after:duration-300 group-hover:after:w-full">
            Open logbook
          </span>
        </div>
      </a>

      {{-- How it Works --}}
      <a href="{{ route('how_it_works') }}"
        class="lg:col-span-2 group relative overflow-hidden rounded-2xl border border-white/10 bg-slate-900/60
              ring-1 ring-white/10 shadow-xl hover:scale-[1.01] transition">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(34,211,238,0.10),transparent_60%)]"></div>
        <div class="relative p-6 sm:p-8">
          <h3 class="mt-2 text-2xl font-semibold">How Vizzbud Works</h3>
          <p class="mt-2 text-sm text-white/70">
            Learn how to read the dive site rings, understand forecasts, and where Vizzbud’s live data comes from.
          </p>
          <span class="mt-6 inline-block text-cyan-400 relative
                      after:absolute after:-bottom-1 after:left-0 after:h-0.5 after:w-0
                      after:bg-cyan-400 after:transition-all after:duration-300 group-hover:after:w-full">
            Learn more
          </span>
        </div>
      </a>

    </div>
  </div>
</section>

@endsection