@extends('layouts.vizzbud')

@section('title', $diveSite->name . ' | Dive Site Info')
@section('meta_description', Str::limit(strip_tags($diveSite->description), 160))

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

@push('head')
  <link href="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css" rel="stylesheet">
@endpush

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

    {{-- Centered Title + Location + Credit (moved lower into the image) --}}
    <div class="absolute bottom-4 left-0 right-0 flex flex-col items-center text-center px-4">
      <h1 class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight drop-shadow-md mb-1">
        {{ $diveSite->name }}
      </h1>

      <p class="text-slate-300 text-xs sm:text-sm font-medium mb-0.5">
        {{ $diveSite->region }}, {{ $diveSite->country }}
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

{{-- 🌊 Compact Info Bar (pill style, full-width mobile, subtle margin) --}}
<section class="w-full flex justify-center my-4 sm:my-6 px-3 sm:px-0">
  <div class="flex flex-wrap items-center justify-center sm:inline-flex
              w-full sm:w-auto
              bg-white/10 backdrop-blur-2xl border border-white/15 ring-1 ring-white/10 shadow-md
              rounded-full divide-x divide-white/10 overflow-hidden
              py-1.5 px-2 sm:px-3 sm:py-1.5 mx-auto max-w-[95%] sm:max-w-none">

    @php
      $items = [
        ['icon' => 'diver.svg', 'label' => $diveSite->suitability],
        ['icon' => $diveSite->dive_type === 'boat' ? 'boat.svg' : 'beach.svg', 'label' => ucfirst($diveSite->dive_type)],
        ['icon' => 'pool-depth.svg', 'label' => 'Avg ' . number_format($diveSite->avg_depth, 0) . 'm'],
        ['icon' => 'under-water.svg', 'label' => 'Max ' . number_format($diveSite->max_depth, 0) . 'm'],
      ];
    @endphp

    @foreach ($items as $item)
      <div class="flex flex-col sm:flex-row items-center justify-center gap-0.5 sm:gap-1
                  flex-1 sm:flex-none
                  px-2 sm:px-3 py-0.5 sm:py-1">
        <img src="/icons/{{ $item['icon'] }}"
             class="w-3.5 h-3.5 sm:w-4 sm:h-4 invert opacity-80"
             alt="">
        <span class="text-[11px] sm:text-[12px] text-white/90 font-medium tracking-tight leading-none">
          {{ $item['label'] }}
        </span>
      </div>
    @endforeach
  </div>
</section>

  {{-- 🌊 Conditions + Forecast (Stacked Layout, beginner-friendly) --}}
  <section class="max-w-5xl mx-auto mb-16 px-4 sm:px-8 space-y-10 sm:space-y-8">

    {{-- 🌊 Current Conditions --}}
    @if($c)
      @php 
        $status = strtolower(optional($diveSite->latestCondition)->status ?? '');
        $classes = match ($status) {
            'green'  => 'bg-emerald-500/20 border-emerald-400/40 text-emerald-100',
            'yellow' => 'bg-amber-400/25 border-amber-300/40 text-amber-100',
            'red'    => 'bg-rose-500/25 border-rose-400/40 text-rose-100',
            default  => 'bg-slate-500/20 border-slate-400/40 text-slate-200',
        };
      @endphp

      <div class="rounded-3xl bg-white/10 backdrop-blur-2xl border border-white/15 
                  ring-1 ring-{{ $accent }}-500/20 shadow-xl p-6 sm:p-8">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5 text-center sm:text-left">
          <h2 class="text-white font-semibold text-lg flex items-center gap-2 justify-center sm:justify-start">
            Current Conditions
          </h2>

          {{-- Live Status Pill --}}
          <div class="flex items-center justify-center sm:justify-end gap-2 rounded-full px-3 py-1.5 
                      border {{ $classes }} backdrop-blur-sm shadow-sm w-fit mx-auto sm:mx-0">
            <span class="relative inline-flex w-2.5 h-2.5">
              <span class="absolute inset-0 rounded-full opacity-50 animate-ping
                {{ $status === 'green' ? 'bg-emerald-300' : ($status === 'yellow' ? 'bg-amber-300' : ($status === 'red' ? 'bg-rose-300' : 'bg-slate-300')) }}"></span>
              <span class="relative inline-flex w-2.5 h-2.5 rounded-full
                {{ $status === 'green' ? 'bg-emerald-400' : ($status === 'yellow' ? 'bg-amber-400' : ($status === 'red' ? 'bg-rose-400' : 'bg-slate-400')) }}"></span>
            </span>

            <span class="text-xs uppercase tracking-wide font-medium">Live</span>
            <span class="text-[13px] font-semibold">
              @if ($status === 'green') Good now
              @elseif ($status === 'yellow') Fair now
              @elseif ($status === 'red') Poor now
              @else Unavailable
              @endif
            </span>
          </div>
        </div>

        {{-- Metrics grid (3 on mobile → 6 on desktop) --}}
        <div class="grid grid-cols-3 lg:grid-cols-6 gap-3 text-sm mb-2">
          @php
            $metrics = [
              ['wave.svg', 'Swell', $c->wave_height ? number_format($c->wave_height, 1).' m' : '–', 'Wave height in metres'],
              ['compass.svg', 'Dir', $c->wave_direction !== null ? CompassHelper::fromDegrees($c->wave_direction) : '—', 'Wave direction'],
              ['tools-and-utensils.svg', 'Period', $c->wave_period ? number_format($c->wave_period, 0).' s' : '–', 'Time between waves (seconds)'],
              ['wind.svg', 'Wind', $c->wind_speed ? number_format($c->wind_speed * 1.94384, 0).' kn' : '–', 'Surface wind speed in knots'],
              ['temperature.svg', 'Water', $c->water_temperature ? number_format($c->water_temperature, 1).' °C' : '–', 'Water temperature at depth'],
              ['temperature.svg', 'Air', $c->air_temperature ? number_format($c->air_temperature, 1).' °C' : '–', 'Air temperature above surface'],
            ];
          @endphp

          @foreach($metrics as [$icon, $label, $value, $hint])
            <div class="flex flex-col items-center justify-center p-3 rounded-xl 
                        bg-white/5 border border-white/10 text-slate-200 hover:bg-white/10 transition"
                title="{{ $hint }}">
              <img src="/icons/{{ $icon }}" class="w-5 h-5 invert mb-1 opacity-90" alt="{{ $label }}">
              <span class="text-xs uppercase tracking-wide text-white/60">{{ $label }}</span>
              <strong class="text-sm text-white mt-0.5">{{ $value }}</strong>
            </div>
          @endforeach
        </div>
      </div>
    @endif

    {{-- 🌅 3-Day Diveability Forecast --}}
    @if(!empty($daypartForecasts ?? []))
      <div class="rounded-3xl bg-white/10 backdrop-blur-2xl border border-white/15 
                  ring-1 ring-{{ $accent }}-500/20 shadow-xl p-6 sm:p-8 w-full">
        <h2 class="text-white font-semibold text-lg text-center mb-3">3-Day Dive Forecast</h2>
        <p class="text-center text-xs text-white/60 mb-6">
          Each segment shows expected dive conditions for different parts of the day.
        </p>

        {{-- Forecast Grid (now full width like the other cards) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-8">
          @foreach ($daypartForecasts as $day)
            @php $date = \Carbon\Carbon::parse($day['date']); @endphp

            {{-- Forecast Card --}}
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 flex flex-col items-center 
                        text-center hover:bg-white/10 transition h-full">
              
              {{-- Header --}}
              <div class="mb-3">
                <p class="text-white font-semibold text-sm leading-tight">
                  {{ $date->isToday() ? 'Today' : $date->format('D') }}
                </p>
                <p class="text-[11px] text-white/60 leading-tight">{{ $date->format('j M') }}</p>
              </div>

              {{-- Day-parts row (icon + label + time) --}}
              <div class="flex justify-center gap-2 w-full">
                @php
                  $parts = [
                    ['label' => 'Morning', 'time' => '6am – 11am', 'key' => 'morning', 'icon' => 'morning.svg'],
                    ['label' => 'Afternoon', 'time' => '12pm – 4pm', 'key' => 'afternoon', 'icon' => 'afternoon.svg'],
                    ['label' => 'Night', 'time' => '5pm – 9pm', 'key' => 'night', 'icon' => 'night.svg'],
                  ];
                @endphp

                @foreach ($parts as $part)
                  @php
                    $status = strtolower($day[$part['key']] ?? 'unknown');
                    $classes = match ($status) {
                        'green'  => 'bg-emerald-400/20 border-emerald-400/30 text-emerald-100',
                        'yellow' => 'bg-amber-400/20 border-amber-300/30 text-amber-100',
                        'red'    => 'bg-rose-500/20 border-rose-400/30 text-rose-100',
                        default  => 'bg-slate-500/20 border-slate-400/30 text-slate-200',
                    };
                  @endphp

                  <div class="flex flex-col items-center justify-center gap-1.5 rounded-lg 
                              px-3 py-2 text-[11px] font-medium border {{ $classes }} flex-1 
                              backdrop-blur-sm shadow-sm hover:bg-white/10 transition"
                      title="{{ ucfirst($part['label']) }} conditions ({{ $part['time'] }})">
                    <img src="/icons/{{ $part['icon'] }}" class="w-4 h-4 invert opacity-90 mb-0.5" alt="{{ $part['label'] }}">
                    <p class="capitalize font-semibold text-white">{{ $part['label'] }}</p>
                    <p class="text-[10px] text-white/70">{{ $part['time'] }}</p>
                  </div>
                @endforeach
              </div>
            </div>
          @endforeach
        </div>

        {{-- 🌈 Integrated Legend --}}
        <div class="rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm 
                    shadow-inner p-5 text-center">
          <h3 class="text-white font-semibold text-lg text-center mb-3">Dive Condition Key</h3>

          <div class="flex flex-col sm:flex-row justify-center items-stretch gap-4 sm:gap-8 
                      text-[13px] text-white/80 leading-relaxed max-w-3xl mx-auto">

            {{-- Green --}}
            <div class="flex flex-col items-center text-center gap-1.5">
              <span class="w-3 h-3 bg-emerald-400 rounded-full shadow-sm"></span>
              <p class="font-semibold text-white text-sm">Great diving</p>
              <p class="text-xs text-white/70 max-w-[220px]">Calm water, clear visibility, and easy conditions.</p>
            </div>

            {{-- Yellow --}}
            <div class="flex flex-col items-center text-center gap-1.5">
              <span class="w-3 h-3 bg-amber-400 rounded-full shadow-sm"></span>
              <p class="font-semibold text-white text-sm">Dive with caution</p>
              <p class="text-xs text-white/70 max-w-[220px]">Moderate swell or shifting visibility, check before diving.</p>
            </div>

            {{-- Red --}}
            <div class="flex flex-col items-center text-center gap-1.5">
              <span class="w-3 h-3 bg-rose-400 rounded-full shadow-sm"></span>
              <p class="font-semibold text-white text-sm">Not ideal</p>
              <p class="text-xs text-white/70 max-w-[220px]">Strong currents, poor visibility, or unsafe surf.</p>
            </div>

          </div>
        </div>
      </div>
    @endif

    {{-- 🌏 Local Intel --}}
    @if($diveSite->hazards || $diveSite->entry_notes || $diveSite->parking_notes || $diveSite->marine_life || $diveSite->pro_tips)
      <div class="rounded-3xl p-6 sm:p-8 bg-white/10 backdrop-blur-2xl 
                  border border-white/15 ring-1 ring-white/10 shadow-xl w-full text-center mb-16">
        <h2 class="text-white font-semibold text-lg sm:text-xl mb-6">
          Local Intel
        </h2>

        <div class="space-y-3 text-sm text-slate-300 text-left sm:text-center sm:mx-auto sm:max-w-3xl">
          @if($diveSite->hazards)
            <p><strong class="text-white">Hazards:</strong> {{ $diveSite->hazards }}</p>
          @endif

          @if($diveSite->entry_notes)
            <p><strong class="text-white">Entry Notes:</strong> {{ $diveSite->entry_notes }}</p>
          @endif

          @if($diveSite->parking_notes)
            <p><strong class="text-white">Parking Info:</strong> {{ $diveSite->parking_notes }}</p>
          @endif

          @if($diveSite->marine_life)
            <p><strong class="text-white">Marine Life:</strong> {{ $diveSite->marine_life }}</p>
          @endif
        </div>

        @if($diveSite->pro_tips)
          <div class="mt-6 rounded-xl bg-amber-500/10 border border-amber-400/30 p-4 sm:mx-auto sm:max-w-3xl">
            <p class="text-amber-100 text-sm leading-relaxed">
              <strong class="text-amber-300">Pro Tip:</strong> {{ $diveSite->pro_tips }}
            </p>
          </div>
        @endif
      </div>
    @endif

    {{-- 📖 About Section --}}
    @php
      $hasAboutContent = $diveSite->description || $diveSite->history || $diveSite->what_to_see || $diveSite->recommended_level;
    @endphp

    @if ($hasAboutContent)
      <div class="rounded-3xl p-6 sm:p-8 bg-white/10 backdrop-blur-2xl 
                  border border-white/15 ring-1 ring-white/10 shadow-xl w-full text-center">
        
        {{-- Centered Title --}}
        <h2 class="text-white font-semibold text-lg sm:text-xl mb-6">
          About This Site
        </h2>

        {{-- Content --}}
        <div class="space-y-6 text-slate-300 leading-relaxed text-[15px] text-left sm:text-center sm:mx-auto sm:max-w-3xl">
          @if($diveSite->description)
            <p class="text-white/90 text-base sm:text-[17px] leading-relaxed">
              {{ $diveSite->description }}
            </p>
          @endif

          @if($diveSite->history)
            <div>
              <h4 class="text-white font-semibold text-sm uppercase tracking-wide mb-1">
                History
              </h4>
              <p>{{ $diveSite->history }}</p>
            </div>
          @endif

          @if($diveSite->what_to_see)
            <div>
              <h4 class="text-white font-semibold text-sm uppercase tracking-wide mb-1">
                What to See
              </h4>
              <p>{{ $diveSite->what_to_see }}</p>
            </div>
          @endif

          @if($diveSite->recommended_level)
            <div>
              <h4 class="text-white font-semibold text-sm uppercase tracking-wide mb-1">
                Recommended Level
              </h4>
              <p>{{ ucfirst($diveSite->recommended_level) }}</p>
            </div>
          @endif
        </div>
      </div>
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

            <a href="{{ route('dive-sites.show', $site->slug) }}" 
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
                    @if($site->region || $site->country)
                      {{ $site->region ? $site->region . ', ' : '' }}{{ $site->country }}
                      <span class="opacity-50 mx-1">•</span>
                    @endif
                    {{ number_format($site->distance, 1) }} km away
                  </p>
                </div>
              </div>
            </a>
          @endforeach
        </div>
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