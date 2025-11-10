@extends('layouts.vizzbud')

@section('title', "{$diveSite->name} Dive Guide | Conditions, Entry & Marine Life")
@section('meta_description', "How to dive {{ $diveSite->name }} — entry points, depth, hazards, marine life, and live dive conditions in {{ $diveSite->region->name }}, {{ $diveSite->region->state->name }}, {{ $diveSite->region->state->country->name }}.")

{{-- 🌍 Open Graph / Twitter --}}
@section('og_title', "{$diveSite->name} Dive Guide | {{ $diveSite->region->name }}")
@section('og_description', "Explore {{ $diveSite->name }}: live dive conditions, entry info, depth details, marine life, and local dive tips.")
@section('og_image', asset(optional($diveSite->photos()->where('is_featured', true)->first())->image_path ?? 'images/divesites/default.webp'))
@section('og_type', 'article')
@section('og_locale', 'en_AU')
@section('twitter_card', 'summary_large_image')
@section('twitter_title', "{$diveSite->name} Dive Guide | {{ $diveSite->region->name }}")
@section('twitter_description', "How to dive {{ $diveSite->name }}: entry, hazards & current conditions in {{ $diveSite->region->state->name }}.")
@section('twitter_image', asset(optional($diveSite->photos()->where('is_featured', true)->first())->image_path ?? 'images/divesites/default.webp'))

@push('head')
  {{-- Article metadata (for Facebook/Twitter rich previews) --}}
  <meta property="article:section" content="Dive Guides">
  <meta property="article:published_time" content="{{ $diveSite->created_at->toIso8601String() }}">
  <meta property="article:modified_time" content="{{ $diveSite->updated_at->toIso8601String() }}">

  {{-- Canonical URL for consistent SEO --}}
  <link rel="canonical" href="{{ url()->current() }}">

  {{-- ✅ Structured Data for Dive Site Page --}}
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "TouristAttraction",
    "name": "{{ $diveSite->name }}",
    "description": "{{ Str::limit(strip_tags($diveSite->description ?? 'Dive site details and conditions.'), 160) }}",
    "image": "{{ asset(optional($diveSite->photos()->where('is_featured', true)->first())->image_path ?? 'images/divesites/default.webp') }}",
    "url": "{{ url()->current() }}",
    "geo": {
      "@type": "GeoCoordinates",
      "latitude": {{ $diveSite->lat }},
      "longitude": {{ $diveSite->lng }}
    },
    "address": {
      "@type": "PostalAddress",
      "addressRegion": "{{ $diveSite->region->state->name ?? '' }}",
      "addressCountry": "{{ $diveSite->region->state->country->name ?? '' }}"
    },
    "isAccessibleForFree": true,
    "touristType": "Scuba Divers",
    "containedInPlace": {
      "@type": "Place",
      "name": "{{ $diveSite->region->name }}, {{ $diveSite->region->state->country->name }}"
    }
  }
  </script>

  {{-- Breadcrumbs for better SERP context --}}
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
      {
        "@type": "ListItem",
        "position": 1,
        "name": "Dive Sites",
        "item": "https://vizzbud.com/dive-sites"
      },
      {
        "@type": "ListItem",
        "position": 2,
        "name": "{{ $diveSite->region->state->country->name }}",
        "item": "https://vizzbud.com/dive-sites/{{ $diveSite->region->state->country->slug }}"
      },
      {
        "@type": "ListItem",
        "position": 3,
        "name": "{{ $diveSite->region->state->name }}",
        "item": "https://vizzbud.com/dive-sites/{{ $diveSite->region->state->country->slug }}/{{ $diveSite->region->state->slug }}"
      },
      {
        "@type": "ListItem",
        "position": 4,
        "name": "{{ $diveSite->region->name }}",
        "item": "https://vizzbud.com/dive-sites/{{ $diveSite->region->state->country->slug }}/{{ $diveSite->region->state->slug }}/{{ $diveSite->region->slug }}"
      },
      {
        "@type": "ListItem",
        "position": 5,
        "name": "{{ $diveSite->name }}",
        "item": "{{ url()->current() }}"
      }
    ]
  }
  </script>

  {{-- Mapbox Styles --}}
  <link href="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css" rel="stylesheet">
@endpush

@php
  use App\Helpers\CompassHelper;
  $c = $diveSite->latestCondition;
  $status = strtolower(optional($c)->status ?? '');
  $accent = match($status) {
      'green'  => 'emerald',
      'yellow' => 'amber',
      'red'    => 'rose',
      default  => 'cyan',
  };
@endphp

@section('content')
<section class="relative">

  {{-- 🌅 Hero --}}
  <div class="relative mb-0">
    @php
      $featuredPhoto = $diveSite->photos()->where('is_featured', true)->first();
      $heroImage = $featuredPhoto ? asset($featuredPhoto->image_path) : asset('images/divesites/default.webp');
      $photoArtist = null;
      $photoCreditLink = null;

      if ($featuredPhoto) {
          if ($featuredPhoto->artist_instagram) {
              $photoArtist = '@' . $featuredPhoto->artist_instagram;
              $photoCreditLink = 'https://www.instagram.com/' . $featuredPhoto->artist_instagram;
          } elseif ($featuredPhoto->artist_name) {
              $photoArtist = $featuredPhoto->artist_name;
          }
      }
    @endphp

    {{-- Background Image --}}
    <img 
      src="{{ $heroImage }}" 
      alt="{{ $diveSite->name }} featured image"
      class="w-full h-[320px] sm:h-[460px] object-cover rounded-b-3xl border border-white/20 shadow-2xl"
    />

    {{-- Overlay Gradient --}}
    <div class="absolute inset-0 bg-gradient-to-t rounded-b-3xl from-slate-900/90 via-slate-900/40 to-transparent"></div>

    <div class="absolute bottom-4 left-0 right-0 flex flex-col items-center text-center px-4">
      <h1 class="text-3xl sm:text-4xl font-extrabold text-white mb-2">
        {{ $diveSite->name }}
      </h1>
      <div itemprop="geo" itemscope itemtype="https://schema.org/GeoCoordinates" class="hidden">
        <meta itemprop="latitude" content="{{ $diveSite->lat }}">
        <meta itemprop="longitude" content="{{ $diveSite->lng }}">
      </div>
      <p class="text-slate-300 text-sm">
        {{ optional($diveSite->region)->name ?? 'Unknown Region' }},
        {{ optional($diveSite->region?->state)->abbreviation ?? optional($diveSite->region?->state)->name ?? 'Unknown State' }},
        {{ optional($diveSite->region?->state?->country)->name ?? 'Unknown Country' }}
      </p>

      {{-- 📸 Image Credit --}}
      @if($featuredPhoto && ($photoArtist || $photoCreditLink))
        <p class="text-[10px] sm:text-[11px] text-white/60 mt-1">
          Photo by 
          @if($photoCreditLink)
            <a href="{{ $photoCreditLink }}"
              target="_blank"
              rel="noopener noreferrer"
              class="underline hover:text-white font-medium">
              {{ $photoArtist }}
            </a>
          @else
            <span class="font-medium">{{ $photoArtist }}</span>
          @endif
        </p>
      @endif
    </div>
  </div>

  @if(session('success'))
    <div 
      x-data="{ show: true }"
      x-show="show"
      x-transition:enter="transition ease-out duration-500"
      x-transition:enter-start="opacity-0 -translate-y-4"
      x-transition:enter-end="opacity-100 translate-y-0"
      x-transition:leave="transition ease-in duration-500"
      x-transition:leave-start="opacity-100 translate-y-0"
      x-transition:leave-end="opacity-0 -translate-y-4"
      x-init="setTimeout(() => show = false, 4000)" {{-- hides after 4 s --}}
      class="fixed top-[7em] left-1/2 -translate-x-1/2 
            px-5 py-2 rounded-full 
            bg-emerald-500/20 border border-emerald-400/30 
            text-emerald-200 text-sm font-medium text-center
            shadow-lg backdrop-blur-md z-50"
    >
      {{ session('success') }}
    </div>
  @endif

    {{-- 🌊 Refined Compact Info Bar (Centered, Fixed-Width Items) --}}
  <section class="w-full flex justify-center my-4 sm:my-6 px-3 sm:px-0">
    <div class="flex flex-wrap items-center justify-center sm:inline-flex
                w-full sm:w-auto
                bg-gradient-to-r from-cyan-500/10 via-slate-900/50 to-indigo-700/10
                backdrop-blur-2xl border border-white/10 ring-1 ring-white/10
                shadow-[0_0_25px_rgba(56,189,248,0.15)]
                rounded-full divide-x divide-white/10 overflow-hidden
                py-1.5 px-2 sm:px-4 mx-auto max-w-[95%] sm:max-w-none">

      @php
        $items = [
          ['icon' => 'diver.svg', 'label' => $diveSite->suitability],
          ['icon' => $diveSite->dive_type === 'boat' ? 'boat.svg' : 'beach.svg', 'label' => ucfirst($diveSite->dive_type)],
          ['icon' => 'pool-depth.svg', 'label' => 'Avg ' . number_format($diveSite->avg_depth, 0) . 'm'],
          ['icon' => 'under-water.svg', 'label' => 'Max ' . number_format($diveSite->max_depth, 0) . 'm'],
        ];
      @endphp

      @foreach ($items as $item)
        <div class="flex flex-col sm:flex-row items-center justify-center text-center
                    min-w-[80px] sm:min-w-[100px] px-2.5 sm:px-3 py-1 sm:py-1.5
                    transition-all duration-200 hover:bg-white/5">
          <img src="/icons/{{ $item['icon'] }}"
              class="w-4 h-4 invert opacity-90 mb-0.5 sm:mb-0 sm:mr-1"
              alt="">
          <span class="text-[11px] sm:text-[12px] text-white/90 font-medium tracking-tight leading-tight break-words">
            {{ $item['label'] }}
          </span>
        </div>
      @endforeach
    </div>
  </section>

  {{-- 🌊 Conditions + Forecast --}}
  <section class="max-w-5xl mx-auto mb-16 px-4 sm:px-8 space-y-10 sm:space-y-8">

  {{-- 🌊 Current Conditions (Dynamic Glow + Responsive Dials) --}}
  <section class="w-full mb-16 px-2 sm:px-6">
    @php 
      $status = strtolower(optional($diveSite->latestCondition)->status ?? '');
      $accent = match ($status) {
          'green'  => 'emerald',
          'yellow' => 'amber',
          'red'    => 'rose',
          default  => 'cyan',
      };
      $glow = match ($status) {
          'green'  => 'shadow-[0_0_45px_rgba(16,185,129,0.3)] ring-emerald-400/30',
          'yellow' => 'shadow-[0_0_45px_rgba(250,204,21,0.25)] ring-amber-400/30',
          'red'    => 'shadow-[0_0_45px_rgba(244,63,94,0.3)] ring-rose-400/30',
          default  => 'shadow-[0_0_45px_rgba(56,189,248,0.2)] ring-cyan-400/30',
      };
    @endphp

    <div class="relative w-full rounded-3xl overflow-hidden
                bg-gradient-to-br from-cyan-500/10 via-slate-900/50 to-indigo-700/10
                backdrop-blur-2xl border border-white/10 ring-1 {{ $glow }}
                p-6 sm:p-10 transition-all duration-500">

      {{-- Glow overlay --}}
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(56,189,248,0.12),transparent_70%)] pointer-events-none"></div>

      {{-- Header --}}
      <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6 text-center sm:text-left">
        <h2 class="text-white font-semibold text-xl sm:text-2xl tracking-tight flex items-center gap-2 justify-center sm:justify-start">
          Current Conditions
        </h2>

        {{-- Live Status Pill --}}
        <div class="flex items-center justify-center gap-2 rounded-full px-3 py-1.5
                    border border-white/10 backdrop-blur-md shadow-md w-fit mx-auto sm:mx-0
                    bg-{{ $accent }}-500/20 text-{{ $accent }}-100
                    ring-1 ring-{{ $accent }}-400/30 shadow-[0_0_25px_rgba(var(--tw-color-{{ $accent }}-400,56,189,248),0.4)]
                    transition-all duration-300">

          {{-- Ping dot --}}
          <span class="relative inline-flex w-2.5 h-2.5">
            <span class="absolute inset-0 rounded-full opacity-50 animate-ping bg-{{ $accent }}-300"></span>
            <span class="relative inline-flex w-2.5 h-2.5 rounded-full bg-{{ $accent }}-400"></span>
          </span>

          {{-- Text inline --}}
          <span class="flex items-center gap-1">
            <span class="text-xs uppercase tracking-wide font-semibold opacity-80">Live:</span>
            <span class="text-[13px] font-semibold capitalize">
              @if ($status === 'green') Good now
              @elseif ($status === 'yellow') Fair now
              @elseif ($status === 'red') Poor now
              @else Unavailable
              @endif
            </span>
          </span>
        </div>
      </div>

      {{-- Swell + Wind Grid --}}
      <div class="grid md:grid-cols-2 gap-10 sm:gap-12 text-center">

        {{-- 🌊 Swell --}}
        <div>
          <h3 class="text-white text-base font-semibold mb-5 flex items-center justify-center gap-2">
            <img src="/icons/wave.svg" class="w-5 h-5 invert opacity-80" alt=""> Swell
          </h3>

          {{-- Dial --}}
          <div class="relative flex items-center justify-center w-28 h-28 sm:w-36 sm:h-36 mx-auto mb-5 transition-transform">
            <div class="absolute inset-0 rounded-full border border-white/10 shadow-[inset_0_0_25px_rgba(255,255,255,0.1)]"></div>

            @foreach (['N','E','S','W'] as $dir)
              <span class="absolute text-[10px] sm:text-[11px] font-semibold text-white/70 drop-shadow-sm
                          {{ $dir === 'N' ? 'top-1' : ($dir === 'S' ? 'bottom-1' : ($dir === 'E' ? 'right-1' : 'left-1')) }}">
                {{ $dir }}
              </span>
            @endforeach

            @for ($i = 0; $i < 360; $i += 45)
              <div class="absolute w-[2px] h-[6px] bg-white/10 origin-center"
                  style="transform: rotate({{ $i }}deg) translateY(-52px);"></div>
            @endfor

            <svg viewBox="0 0 64 64" class="w-20 h-20 sm:w-24 sm:h-24 relative z-10 transition-transform"
                style="transform: rotate({{ ($c->wave_direction ?? 0) + 180 }}deg);">
              <path d="M32 10 L36 26 L32 22 L28 26 Z" fill="#22d3ee" stroke="#0e7490" stroke-width="1.5" />
              <line x1="32" y1="22" x2="32" y2="54" stroke="#22d3ee" stroke-width="1.2" stroke-linecap="round" opacity="0.5"/>
              <circle cx="32" cy="32" r="2" fill="#22d3ee"/>
            </svg>
          </div>

          {{-- Data --}}
          <div class="flex flex-wrap justify-center gap-5 sm:gap-6 text-sm sm:text-base text-white/80">
            <div>
              <div class="text-xl font-semibold text-white">{{ number_format($c->wave_height ?? 0, 1) }}</div>
              <div class="text-xs uppercase text-white/60 mt-1">Height (m)</div>
            </div>
            <div>
              <div class="text-xl font-semibold text-white">{{ number_format($c->wave_period ?? 0, 0) }}</div>
              <div class="text-xs uppercase text-white/60 mt-1">Period (s)</div>
            </div>
            <div>
              <div class="text-lg font-semibold text-cyan-300">{{ $c->wave_direction !== null ? CompassHelper::fromDegrees($c->wave_direction) : '—' }}</div>
              <div class="text-xs uppercase text-white/60 mt-1">Direction</div>
            </div>
            <div>
              <div class="text-lg font-semibold text-cyan-200">{{ $c->water_temperature ? number_format($c->water_temperature, 1) : '—' }}</div>
              <div class="text-xs uppercase text-white/60 mt-1">Water °C</div>
            </div>
          </div>
        </div>

        {{-- 🌬️ Wind --}}
        <div>
          <h3 class="text-white text-base font-semibold mb-5 flex items-center justify-center gap-2">
            <img src="/icons/wind.svg" class="w-5 h-5 invert opacity-80" alt=""> Wind
          </h3>

          {{-- Dial --}}
          <div class="relative flex items-center justify-center w-28 h-28 sm:w-36 sm:h-36 mx-auto mb-5 transition-transform">
            <div class="absolute inset-0 rounded-full border border-white/10 shadow-[inset_0_0_25px_rgba(255,255,255,0.1)]"></div>

            @foreach (['N','E','S','W'] as $dir)
              <span class="absolute text-[10px] sm:text-[11px] font-semibold text-white/70 drop-shadow-sm
                          {{ $dir === 'N' ? 'top-1' : ($dir === 'S' ? 'bottom-1' : ($dir === 'E' ? 'right-1' : 'left-1')) }}">
                {{ $dir }}
              </span>
            @endforeach

            @for ($i = 0; $i < 360; $i += 45)
              <div class="absolute w-[2px] h-[6px] bg-white/10 origin-center"
                  style="transform: rotate({{ $i }}deg) translateY(-52px);"></div>
            @endfor

            <svg viewBox="0 0 64 64" class="w-20 h-20 sm:w-24 sm:h-24 relative z-10 transition-transform"
                style="transform: rotate({{ ($c->wind_direction ?? 0) + 180 }}deg);">
              <path d="M32 10 L36 26 L32 22 L28 26 Z" fill="#60a5fa" stroke="#3b82f6" stroke-width="1.5" />
              <line x1="32" y1="22" x2="32" y2="54" stroke="#60a5fa" stroke-width="1.2" stroke-linecap="round" opacity="0.5"/>
              <circle cx="32" cy="32" r="2" fill="#60a5fa"/>
            </svg>
          </div>

          {{-- Data --}}
          <div class="flex flex-wrap justify-center gap-5 sm:gap-6 text-sm sm:text-base text-white/80">
            <div>
              <div class="text-xl font-semibold text-white">{{ number_format(($c->wind_speed ?? 0) * 1.94384, 0) }}</div>
              <div class="text-xs uppercase text-white/60 mt-1">Speed (kn)</div>
            </div>
            <div>
              <div class="text-xl font-semibold text-white">{{ number_format($c->air_temperature ?? 0, 1) }}</div>
              <div class="text-xs uppercase text-white/60 mt-1">Air °C</div>
            </div>
            <div>
              <div class="text-lg font-semibold text-indigo-300">{{ $c->wind_direction !== null ? CompassHelper::fromDegrees($c->wind_direction) : '—' }}</div>
              <div class="text-xs uppercase text-white/60 mt-1">Direction</div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

{{-- 🌊 3-Day Dive Forecast (Thin Mobile + Full Color) --}}
@if(!empty($daypartForecasts ?? []))
  <div class="rounded-2xl bg-gradient-to-br from-cyan-500/10 via-slate-900/50 to-indigo-700/10
              backdrop-blur-2xl border border-white/15 ring-1 ring-white/10
              shadow-[0_0_35px_rgba(56,189,248,0.25)] p-3 sm:p-8 space-y-4">

    <div class="text-center">
      <h2 class="text-white font-bold text-base sm:text-2xl mb-1 tracking-tight">3-Day Dive Forecast</h2>
      <p class="text-white/70 text-[11px] sm:text-sm">Average swell, wind, and period per part of the day — includes max swell.</p>
      <p class="text-cyan-400 font-medium text-[10px] sm:text-xs mt-1">Updated daily at 5 am</p>
    </div>

    @foreach ($daypartForecasts as $i => $day)
      @php
        $date = \Carbon\Carbon::parse($day['date']);
        $label = $date->isToday() ? 'Today' : ($i === 1 ? 'Tomorrow' : '+2 Days');
        $parts = [
          ['label'=>'Morning','key'=>'morning','icon'=>'morning.svg','time'=>'6–11 am'],
          ['label'=>'Midday','key'=>'afternoon','icon'=>'afternoon.svg','time'=>'12–4 pm'],
          ['label'=>'Evening','key'=>'night','icon'=>'night.svg','time'=>'5–9 pm'],
        ];
      @endphp

      <div class="rounded-xl border border-white/10 bg-slate-800/40 sm:bg-transparent p-2 sm:p-4 shadow-inner">
        <div class="flex items-center justify-center text-white font-semibold text-xs sm:text-base mb-2">{{ $label }}</div>

        <div class="flex flex-col gap-2 sm:grid sm:grid-cols-3 sm:gap-3">
          @foreach ($parts as $part)
            @php
              $p = $day[$part['key']] ?? [];
              $wave = $p['wave'] ?? null;
              $waveMax = $p['wave_max'] ?? null;
              $period = $p['period'] ?? null;
              $wind = $p['wind'] ?? null;
              $status = strtolower($p['status'] ?? 'unknown');

              $color = match ($status) {
                'green'  => 'bg-emerald-600/40 border-emerald-400/40 text-emerald-100',
                'yellow' => 'bg-yellow-400/40 border-yellow-300/50 text-yellow-50', /* brighter yellow */
                'red'    => 'bg-rose-700/40 border-rose-500/40 text-rose-100',
                default  => 'bg-slate-700/40 border-slate-500/30 text-white/80',
              };
            @endphp

            {{-- Thin horizontal bar on mobile, full grid card on desktop --}}
            <div class="flex flex-row sm:flex-col items-center justify-between sm:justify-center 
                        gap-2 sm:gap-3 border {{ $color }} rounded-md sm:rounded-xl px-2 py-1.5 sm:p-3 
                        backdrop-blur-md hover:scale-[1.01] transition-all duration-200">
              
              {{-- Left side: icon + label --}}
              <div class="flex items-center gap-1.5 min-w-[75px]">
                <img src="/icons/{{ $part['icon'] }}" class="w-4 h-4 sm:w-5 sm:h-5 invert opacity-90">
                <div class="flex flex-col text-left sm:text-center">
                  <span class="text-[11px] sm:text-[12px] font-semibold leading-tight">{{ $part['label'] }}</span>
                  <span class="text-[10px] sm:text-[11px] text-white/60 sm:hidden">{{ $part['time'] }}</span>
                </div>
              </div>

              {{-- Middle: average swell --}}
              <div class="flex flex-col items-center flex-1 sm:mt-0">
                <span class="text-[13px] sm:text-xl font-bold leading-tight">
                  {{ $wave ? number_format($wave, 1).' m' : '–' }}
                </span>
                <span class="text-[10px] sm:text-[12px] text-white/80">Average Swell</span>
              </div>

              {{-- Right side: compact stats (visible everywhere) --}}
              <div class="flex items-center gap-3 sm:grid sm:grid-cols-3 sm:gap-2 text-[10px] sm:text-[11px] text-white/70">
                <div class="text-center">
                  <span class="block text-[12px] sm:text-base font-semibold text-white/90 leading-tight">{{ $wind ? number_format($wind, 0) : '–' }}</span>
                  <span class="opacity-70 text-[10px]">Wind</span>
                </div>
                <div class="text-center">
                  <span class="block text-[12px] sm:text-base font-semibold text-white/90 leading-tight">{{ $period ? number_format($period, 0) : '–' }}</span>
                  <span class="opacity-70 text-[10px]">Period</span>
                </div>
                <div class="text-center">
                  <span class="block text-[12px] sm:text-base font-semibold text-white/90 leading-tight">{{ $waveMax ? number_format($waveMax, 1) : '–' }}</span>
                  <span class="opacity-70 text-[10px]">Max</span>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @endforeach
  </div>
@endif

    @php
    $hasDiveInfo =
        !empty($diveSite->description) ||
        !empty($diveSite->entry_notes) ||
        !empty($diveSite->parking_notes) ||
        !empty($diveSite->hazards) ||
        !empty($diveSite->marine_life) ||
        !empty($diveSite->pro_tips) ||
        !empty($diveSite->map_image_path);
    @endphp

    @if($hasDiveInfo)
      <article itemscope itemtype="https://schema.org/Article" class="rounded-3xl p-6 sm:p-10 bg-white/10 backdrop-blur-2xl 
                      border border-white/15 ring-1 ring-white/10 shadow-xl w-full text-center mb-16">
        
        <h2 class="text-white font-semibold text-xl sm:text-2xl mb-6 tracking-tight">
          How to Dive {{ $diveSite->name }}
        </h2>

        {{-- 🗺️ Dive Map --}}
        @if($diveSite->map_image_path)
          <div class="w-full mb-6">
            <div class="relative w-full overflow-hidden rounded-3xl border border-white/10 shadow-2xl">
              <img 
                src="{{ asset($diveSite->map_image_path) }}" 
                alt="Dive map for {{ $diveSite->name }}"
                class="w-full h-auto max-h-[80vh] object-contain bg-slate-900/40"
                loading="lazy"
                decoding="async"
              >
            </div>

            @if($diveSite->map_caption)
              <p class="text-xs text-white/60 mt-3 text-center italic">
                {{ $diveSite->map_caption }}
              </p>
            @endif

            <div class="mt-4 text-center">
              <a 
                href="{{ asset($diveSite->map_image_path) }}" 
                target="_blank" 
                rel="noopener noreferrer"
                class="inline-flex items-center gap-2 text-[13px] text-cyan-300 hover:text-cyan-200 font-medium transition"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M14 3h7m0 0v7m0-7L10 14m-1 7H5a2 2 0 01-2-2v-4" />
                </svg>
                Open full map in new tab
              </a>
            </div>
          </div>
        @endif

        <div class="space-y-10 mt-6 text-slate-300 leading-relaxed text-[15px] 
                text-left sm:text-center sm:max-w-3xl mx-auto">
          {{-- ✏️ Overview --}}
          @if($diveSite->description)
            <div class="p-6 rounded-2xl bg-white/5 border border-white/10">
              <h3 class="text-white font-semibold text-base uppercase tracking-wide mb-3">Overview</h3>
              <p class="text-white/90 text-base sm:text-[17px] leading-relaxed">
                {{ $diveSite->description }}
              </p>
            </div>
          @endif

          {{-- 🚪 Entry & Access --}}
          @if($diveSite->entry_notes || $diveSite->parking_notes)
            <div class="p-6 rounded-2xl bg-white/5 border border-white/10">
              <h3 class="text-white font-semibold text-base uppercase tracking-wide mb-3">Entry & Access</h3>
              <div class="space-y-2">
                @if($diveSite->entry_notes)
                  <p><strong class="text-white">Entry:</strong> {{ $diveSite->entry_notes }}</p>
                @endif
                @if($diveSite->parking_notes)
                  <p><strong class="text-white">Parking:</strong> {{ $diveSite->parking_notes }}</p>
                @endif
              </div>
            </div>
          @endif

          {{-- ⚠️ Hazards --}}
          @if($diveSite->hazards)
            <div class="p-6 rounded-2xl bg-white/5 border border-white/10">
              <h3 class="text-white font-semibold text-base uppercase tracking-wide mb-3">Hazards</h3>
              <p>{{ $diveSite->hazards }}</p>
            </div>
          @endif

          {{-- 🐠 Marine Life --}}
          @if($diveSite->marine_life)
            <div class="p-6 rounded-2xl bg-white/5 border border-white/10">
              <h3 class="text-white font-semibold text-base uppercase tracking-wide mb-3">Marine Life</h3>
              <p>{{ $diveSite->marine_life }}</p>
            </div>
          @endif

          {{-- 💡 Pro Tips --}}
          @if($diveSite->pro_tips)
            <div class="p-6 rounded-2xl bg-amber-500/10 border border-amber-400/30">
              <h3 class="text-amber-300 font-semibold text-base uppercase tracking-wide mb-3">Pro Tip</h3>
              <p class="text-amber-100 text-sm leading-relaxed">{{ $diveSite->pro_tips }}</p>
            </div>
          @endif

        </div>
    </article>
    @endif

    {{-- 📍 Nearby Dive Sites --}}
    @if(isset($nearbySites) && $nearbySites->isNotEmpty())
      <div class="rounded-3xl p-6 sm:p-8 bg-white/10 backdrop-blur-2xl 
                  border border-white/15 ring-1 ring-white/10 shadow-xl w-full text-center mt-16">
        
        <h2 class="text-white font-semibold text-lg sm:text-xl mb-6">
          Nearby Dive Sites
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-slate-200 max-w-5xl mx-auto">
          @foreach($nearbySites as $site)
            @php
              $thumb = optional($site->photos->first())->image_path 
                ? asset($site->photos->first()->image_path) 
                : asset('images/divesites/default.webp');
            @endphp

            <a href="{{ route('dive-sites.show', $site->getFullRouteParams()) }}"
              class="group block rounded-2xl border border-white/10 bg-white/5 hover:bg-white/10 
                      transition overflow-hidden text-left">
              
              {{-- Thumbnail --}}
              <div class="relative w-full h-44 sm:h-48 overflow-hidden">
                <img 
                  src="{{ $thumb }}" 
                  alt="{{ $site->name }} thumbnail"
                  class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                >
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/70 via-slate-900/30 to-transparent"></div>
                
                <div class="absolute bottom-3 left-3 right-3 text-white drop-shadow-sm">
                  <h3 class="text-base font-semibold leading-tight">{{ $site->name }}</h3>
                  <p class="text-xs text-white/70 mt-0.5">
                    @if($site->region || $site->region?->state)
                      {{ optional($site->region)->name }},
                      {{ optional($site->region?->state)->abbreviation ?? optional($site->region?->state)->name }},
                      {{ optional($site->region?->state?->country)->name }}
                      <span class="opacity-50 mx-1">•</span>
                    @endif
                    {{ number_format($site->distance, 1) }} km away
                  </p>
                </div>
              </div>
            </a>
          @endforeach
        </div>
        <p class="text-slate-400 text-sm mt-8">
          Looking for more? See other 
          @php $regionName = optional($diveSite->region)->name ?? 'this region'; @endphp
          <a href="{{ route('dive-map.index', ['region' => optional($diveSite->region)->slug]) }}" class="text-cyan-400 hover:underline">
            dive sites in {{ $regionName }}
          </a>
        </p>
      </div>
    @endif

    {{-- ✏️ Suggest an Edit --}}
    <section 
      x-data="{ sent: false }" 
      class="rounded-3xl p-6 sm:p-8 bg-white/10 backdrop-blur-2xl 
            border border-white/15 ring-1 ring-white/10 shadow-xl w-full text-center mt-16">

      <template x-if="!sent">
        <div>
          <h2 class="text-white font-semibold text-lg sm:text-xl mb-4">
            Spot something outdated?
          </h2>
          <p class="text-slate-300 text-sm max-w-2xl mx-auto mb-6">
            Help us keep <strong>{{ $diveSite->name }}</strong> accurate — if you notice missing info, incorrect details, 
            or want to share local knowledge, send us a quick note below.
          </p>

          {{-- Form --}}
          <form action="{{ route('suggestions.store') }}" method="POST" 
                class="space-y-4 max-w-md mx-auto"  {{-- 🧭 limit width + center --}}
                x-on:submit="sent = true">
            @csrf
            <input type="hidden" name="dive_site_id" value="{{ $diveSite->id }}">
            <input type="hidden" name="dive_site" value="{{ $diveSite->name }}">

            {{-- 🕵️ Honeypot --}}
            <div class="hidden">
              <label for="website">Leave this field blank</label>
              <input type="text" id="website" name="website" autocomplete="off">
            </div>

            <div>
              <label for="name" class="text-sm text-white/80">Your Name (optional)</label>
              <input type="text" name="name" id="name"
                    class="w-full rounded-md bg-white/10 border border-white/20 text-white p-2 text-sm">
            </div>

            <div>
              <label for="email" class="text-sm text-white/80">Your Email (optional)</label>
              <input type="email" name="email" id="email"
                    class="w-full rounded-md bg-white/10 border border-white/20 text-white p-2 text-sm">
            </div>

            <div>
              <label for="message" class="text-sm text-white/80">What needs updating?</label>
              <textarea name="message" id="message" rows="4" required
                        class="w-full rounded-md bg-white/10 border border-white/20 text-white p-2 text-sm"></textarea>
            </div>

            <button type="submit"
                    class="w-full bg-cyan-600 hover:bg-cyan-500 text-white font-semibold py-2 rounded-md transition">
              Submit Suggestion
            </button>
          </form>
        </div>
      </template>

      <template x-if="sent">
        <div class="py-10">
          <h3 class="text-white font-semibold text-lg mb-2">Thank you!</h3>
          <p class="text-slate-300 text-sm">Your suggestion for <strong>{{ $diveSite->name }}</strong> has been sent successfully.</p>
        </div>
      </template>
    </section>
</section>

@endsection