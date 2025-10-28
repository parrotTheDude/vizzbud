@extends('layouts.vizzbud')
@section('title', 'Dive Sites by Country | Vizzbud')

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
        🌏 Dive Sites by Country
      </h1>
      <p class="text-slate-400 text-sm max-w-2xl mx-auto">
        Explore the world’s top diving destinations. Choose a country to see its dive regions and popular local sites.
      </p>
    </header>

    <!-- 🌍 Country Grid -->
    @if($countries->isEmpty())
      <p class="text-center text-slate-400">No countries available yet.</p>
    @else
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($countries as $country)
          @php
            $flag = $flags[strtoupper($country->code ?? '')] ?? '🌊';
          @endphp

          <a href="{{ route('dive-sites.country', $country->slug) }}"
             class="group relative overflow-hidden rounded-2xl border border-white/10 bg-white/5
                    hover:border-cyan-400/40 hover:bg-white/10 transition-all duration-300 
                    p-6 shadow-md hover:shadow-cyan-500/10 flex flex-col justify-between">
            
            <!-- Glow -->
            <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500">
              <div class="absolute -inset-10 bg-gradient-to-br from-cyan-400/10 via-transparent to-transparent blur-2xl"></div>
            </div>

            <div class="relative z-10 flex items-center gap-3">
              <span class="text-3xl leading-none">{{ $flag }}</span>
              <div>
                <h2 class="text-xl font-semibold text-white group-hover:text-cyan-300 transition-colors">
                  {{ $country->name }}
                </h2>
                <p class="text-sm text-slate-400 mt-1">
                  {{ $country->states_count }} {{ Str::plural('region', $country->states_count) }}
                </p>
              </div>
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

  </div>
</div>
@endsection