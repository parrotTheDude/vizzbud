@extends('layouts.vizzbud')
@section('title', $country->name . ' Dive Regions & States | Vizzbud')
@section('meta_description', 'Explore dive regions and states within ' . $country->name . '. Find top scuba diving locations, conditions, and local guides on Vizzbud.')

{{-- 🌍 Open Graph / Twitter --}}
@section('og_title', $country->name . ' Dive Regions & States | Vizzbud')
@section('og_description', 'Discover ' . $country->name . ' dive destinations — explore each region’s top dive sites and local conditions.')
@section('og_image', asset('images/divesites/default.webp'))
@section('twitter_title', $country->name . ' Dive Regions & States | Vizzbud')
@section('twitter_description', 'Explore scuba diving regions and dive sites across ' . $country->name . ' on Vizzbud.')
@section('twitter_image', asset('images/divesites/default.webp'))

@push('head')
  {{-- Canonical --}}
  <link rel="canonical" href="{{ route('dive-sites.country', $country->slug) }}">

  {{-- Structured Data --}}
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "CollectionPage",
    "name": "{{ $country->name }} Dive Regions",
    "description": "Browse scuba diving regions and states within {{ $country->name }} and explore each area's dive sites and local conditions.",
    "url": "{{ route('dive-sites.country', $country->slug) }}",
    "isPartOf": {
      "@type": "WebSite",
      "name": "Vizzbud",
      "url": "https://vizzbud.com"
    },
    "mainEntity": {
      "@type": "ItemList",
      "itemListElement": [
        @foreach($states as $index => $state)
        {
          "@type": "ListItem",
          "position": {{ $index + 1 }},
          "name": "{{ $state->name }}",
          "url": "{{ route('dive-sites.state', [$country->slug, $state->slug]) }}"
        }@if(!$loop->last),@endif
        @endforeach
      ]
    }
  }
  </script>
@endpush

@section('content')
@php
  // 🌍 Country code → emoji flag
  $flags = [
    'AU' => '🇦🇺', 'NZ' => '🇳🇿', 'ID' => '🇮🇩', 'PH' => '🇵🇭', 'TH' => '🇹🇭',
    'JP' => '🇯🇵', 'US' => '🇺🇸', 'GB' => '🇬🇧', 'CA' => '🇨🇦', 'MX' => '🇲🇽',
    'FR' => '🇫🇷', 'ES' => '🇪🇸', 'IT' => '🇮🇹', 'EG' => '🇪🇬', 'ZA' => '🇿🇦',
    'BR' => '🇧🇷', 'VN' => '🇻🇳', 'MY' => '🇲🇾', 'FJ' => '🇫🇯'
  ];
@endphp

<div class="min-h-screen bg-gradient-to-b from-slate-900 to-slate-800 text-slate-100 py-12 px-4 sm:px-6">
  <div class="max-w-6xl mx-auto space-y-10">

    <!-- 🗺️ Header -->
    <header class="text-center">
      <h1 class="text-3xl sm:text-4xl font-bold text-cyan-400 mb-2">
        {{ $flags[strtoupper($country->code)] ?? '🌊' }} {{ $country->name }}
      </h1>
      <p class="text-slate-400 text-sm max-w-2xl mx-auto">
        Select a region or state within {{ $country->name }} to explore its dive sites and conditions.
      </p>
    </header>

    <!-- 🌍 State Grid -->
    @if($states->isEmpty())
      <p class="text-center text-slate-400">No regions or states available yet for this country.</p>
    @else
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($states as $state)
          <a href="{{ route('dive-sites.state', [$country->slug, $state->slug]) }}"
             class="group relative overflow-hidden rounded-2xl border border-white/10 bg-white/5
                    hover:border-cyan-400/40 hover:bg-white/10 transition-all duration-300 
                    p-6 shadow-md hover:shadow-cyan-500/10 flex flex-col justify-between">
            
            <!-- Subtle glow -->
            <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500">
              <div class="absolute -inset-10 bg-gradient-to-br from-cyan-400/10 via-transparent to-transparent blur-2xl"></div>
            </div>

            <div class="relative z-10">
              <h2 class="text-xl font-semibold text-white group-hover:text-cyan-300 transition-colors">
                {{ $state->name }}
              </h2>
              <p class="text-sm text-slate-400 mt-1">
                {{ $state->regions_count }} {{ Str::plural('region', $state->regions_count) }}
              </p>
            </div>

            <div class="relative z-10 mt-4 flex items-center text-cyan-400 text-sm font-medium group-hover:text-cyan-300 transition">
              Explore
              <svg class="ml-1.5 w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
              </svg>
            </div>
          </a>
        @endforeach
      </div>
    @endif

    <!-- Back link -->
    <div class="text-center mt-10">
      <a href="{{ route('dive-sites.countries') }}"
         class="inline-flex items-center text-sm text-slate-400 hover:text-cyan-300 transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Back to all countries
      </a>
    </div>

  </div>
</div>
@endsection