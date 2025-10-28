@extends('layouts.vizzbud')
@section('title', $region->name . ' Dive Sites | Vizzbud')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-slate-900 to-slate-800 text-slate-100 py-12 px-4 sm:px-6">
  <div class="max-w-6xl mx-auto space-y-10">

    <!-- 🗺️ Header -->
    <header class="text-center">
      <h1 class="text-3xl sm:text-4xl font-bold text-cyan-400 mb-2">
        {{ $region->name }}, {{ $state->name }}
      </h1>
      <p class="text-slate-400 text-sm max-w-2xl mx-auto">
        Discover all dive sites in {{ $region->name }} — detailed info, entry notes, hazards, and what you’ll see underwater.
      </p>
    </header>

    <!-- 🐠 Dive Sites Grid -->
    @if($diveSites->isEmpty())
      <p class="text-center text-slate-400">No dive sites added yet for this region.</p>
    @else
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($diveSites as $site)
          <a href="{{ route('dive-sites.show', [$country->slug, $state->slug, $region->slug, $site->slug]) }}"
             class="group relative overflow-hidden rounded-2xl border border-white/10 bg-white/5
                    hover:border-cyan-400/40 hover:bg-white/10 transition-all duration-300 
                    p-6 shadow-md hover:shadow-cyan-500/10 flex flex-col justify-between">

            <!-- Glow -->
            <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500">
              <div class="absolute -inset-10 bg-gradient-to-br from-cyan-400/10 via-transparent to-transparent blur-2xl"></div>
            </div>

            <!-- Info -->
            <div class="relative z-10">
              <h2 class="text-xl font-semibold text-white group-hover:text-cyan-300 transition-colors">
                {{ $site->name }}
              </h2>
              <p class="text-sm text-slate-400 mt-1">
                {{ $site->dive_type ? ucfirst($site->dive_type) . ' dive' : 'Dive site' }}
                @if($site->max_depth)
                  · Max depth {{ $site->max_depth }}m
                @endif
              </p>
            </div>

            <!-- CTA -->
            <div class="relative z-10 mt-4 flex items-center text-cyan-400 text-sm font-medium group-hover:text-cyan-300 transition">
              View Site
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
      <a href="{{ route('dive-sites.state', [$country->slug, $state->slug]) }}"
         class="inline-flex items-center text-sm text-slate-400 hover:text-cyan-300 transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Back to {{ $state->name }}
      </a>
    </div>

  </div>
</div>
@endsection