@extends('layouts.vizzbud')

@section('title', "{$state->name} Dive Regions | {$country->name} | Vizzbud")
@section('meta_description', "Explore scuba dive regions in {$state->name}, {$country->name}. Browse areas and dive sites with live conditions and maps on Vizzbud.")

{{-- 🌍 Open Graph / Twitter --}}
@section('og_title', "{$state->name} Dive Regions | {$country->name}")
@section('og_description', "Discover top dive regions across {$state->name}, {$country->name}. Explore local areas and find nearby dive sites on Vizzbud.")
@section('og_image', asset('images/divesites/default.webp'))
@section('twitter_title', "{$state->name} Dive Regions | {$country->name}")
@section('twitter_description', "Dive into {$state->name}, {$country->name} — browse regions and explore local dive sites with live conditions on Vizzbud.")
@section('twitter_image', asset('images/divesites/default.webp'))

@push('head')
  {{-- Canonical --}}
  <link rel="canonical" href="{{ route('dive-sites.state', [$country->slug, $state->slug]) }}">

  {{-- Structured Data: CollectionPage + BreadcrumbList --}}
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "CollectionPage",
    "name": "{{ $state->name }} Dive Regions",
    "description": "Dive regions within {{ $state->name }}, {{ $country->name }}. Find local dive areas and explore detailed site listings on Vizzbud.",
    "url": "{{ route('dive-sites.state', [$country->slug, $state->slug]) }}",
    "isPartOf": {
      "@type": "WebSite",
      "name": "Vizzbud",
      "url": "https://vizzbud.com"
    },
    "mainEntity": {
      "@type": "ItemList",
      "itemListElement": [
        @foreach($regions as $index => $region)
        {
          "@type": "ListItem",
          "position": {{ $index + 1 }},
          "name": "{{ $region->name }}",
          "url": "{{ route('dive-sites.region', [$country->slug, $state->slug, $region->slug]) }}"
        }@if(!$loop->last),@endif
        @endforeach
      ]
    }
  }
  </script>

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
        "name": "{{ $country->name }}",
        "item": "{{ route('dive-sites.country', $country->slug) }}"
      },
      {
        "@type": "ListItem",
        "position": 3,
        "name": "{{ $state->name }}",
        "item": "{{ route('dive-sites.state', [$country->slug, $state->slug]) }}"
      }
    ]
  }
  </script>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-b from-slate-900 to-slate-800 text-slate-100 py-12 px-4 sm:px-6">
  <div class="max-w-6xl mx-auto space-y-10">

    <!-- 🗺️ Header -->
    <header class="text-center">
      <h1 class="text-3xl sm:text-4xl font-bold text-cyan-400 mb-2">
        {{ $state->name }}, {{ $country->name }}
      </h1>
      <p class="text-slate-400 text-sm max-w-2xl mx-auto">
        Explore all dive regions in {{ $state->name }}. Select one to see dive sites, conditions, and local details.
      </p>
    </header>

    <!-- 🌊 Regions Grid -->
    @if($regions->isEmpty())
      <p class="text-center text-slate-400">No regions available yet for this state.</p>
    @else
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($regions as $region)
          <a href="{{ route('dive-sites.region', [$country->slug, $state->slug, $region->slug]) }}"
             class="group relative overflow-hidden rounded-2xl border border-white/10 bg-white/5
                    hover:border-cyan-400/40 hover:bg-white/10 transition-all duration-300 
                    p-6 shadow-md hover:shadow-cyan-500/10 flex flex-col justify-between">
            
            <!-- Glow -->
            <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500">
              <div class="absolute -inset-10 bg-gradient-to-br from-cyan-400/10 via-transparent to-transparent blur-2xl"></div>
            </div>

            <!-- Region Info -->
            <div class="relative z-10">
              <h2 class="text-xl font-semibold text-white group-hover:text-cyan-300 transition-colors">
                {{ $region->name }}
              </h2>
              <p class="text-sm text-slate-400 mt-1">
                {{ $region->dive_sites_count }} {{ Str::plural('dive site', $region->dive_sites_count) }}
              </p>
            </div>

            <!-- CTA -->
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

    <!-- Back Link -->
    <div class="text-center mt-10">
      <a href="{{ route('dive-sites.country', $country->slug) }}"
         class="inline-flex items-center text-sm text-slate-400 hover:text-cyan-300 transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Back to {{ $country->name }}
      </a>
    </div>

  </div>
</div>
@endsection