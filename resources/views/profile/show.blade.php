@extends('layouts.vizzbud')

@section('title', "{$user->name} | Profile | Vizzbud")
@section('meta_description', "View {$user->name}'s diving profile on Vizzbud — dive logs, stats, and favorite dive sites.")

@push('head')
  {{-- User profile pages should only be indexed if public --}}
  @if($user->is_public ?? false)
    <meta name="robots" content="index,follow">
  @else
    <meta name="robots" content="noindex, nofollow">
  @endif

  {{-- Canonical --}}
  <link rel="canonical" href="{{ route('profile.show', $user->slug ?? $user->id) }}">

  {{-- Open Graph / Twitter --}}
  <meta property="og:title" content="{{ $user->name }}'s Dive Profile | Vizzbud">
  <meta property="og:description" content="Explore {{ $user->name }}'s latest dives, stats, and favorite sites on Vizzbud.">
  <meta property="og:image" content="{{ $user->avatar_url ?? asset('images/divesites/default.webp') }}">
  <meta property="og:type" content="profile">
  <meta property="og:url" content="{{ route('profile.show', $user->slug ?? $user->id) }}">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="{{ $user->name }}'s Dive Profile | Vizzbud">
  <meta name="twitter:description" content="View {{ $user->name }}'s dive log, stats, and favorite sites.">
  <meta name="twitter:image" content="{{ $user->avatar_url ?? asset('images/divesites/default.webp') }}">

  {{-- Structured Data --}}
  @if($user->is_public ?? false)
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Person",
    "name": "{{ $user->name }}",
    "url": "{{ route('profile.show', $user->slug ?? $user->id) }}",
    "image": "{{ $user->avatar_url ?? asset('images/divesites/default.webp') }}",
    "description": "Diver on Vizzbud — logging dives and exploring global dive sites."
  }
  </script>
  @endif
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-b from-slate-900 to-slate-800 text-slate-100 py-10 px-4 sm:px-6">
  <div class="max-w-6xl mx-auto space-y-10">

    {{-- 🧍 Diver Header --}}
    <section 
      class="relative flex flex-col sm:flex-row items-center sm:items-center justify-between gap-8 
            bg-gradient-to-r from-slate-800/80 to-slate-900/60 border border-white/10 
            rounded-2xl p-6 sm:p-8 shadow-lg overflow-hidden">

      {{-- Decorative glow --}}
      <div class="absolute inset-0 bg-gradient-to-r from-cyan-500/10 via-transparent to-cyan-500/10 blur-2xl opacity-30 pointer-events-none"></div>

      {{-- Left: Avatar + Info --}}
      <div class="flex flex-col sm:flex-row items-center sm:items-center gap-6 relative z-10">
        {{-- Avatar --}}
        <div class="relative group">
          <img 
            src="{{ $user->profile->avatar_url ?? asset('images/main/defaultProfile.webp') }}"
            alt="Profile photo"
            class="w-28 h-28 sm:w-32 sm:h-32 rounded-full object-cover border-2 border-cyan-400/40 shadow-md transition-transform duration-300 group-hover:scale-105">
          <div class="absolute inset-0 rounded-full bg-cyan-500/10 opacity-0 group-hover:opacity-100 transition duration-300"></div>
        </div>

        {{-- Text Info --}}
        <div class="text-center sm:text-left">
          <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">
            {{ $user->name }}
          </h1>

          {{-- Certification & start year --}}
          @if($user->profile->diveLevel || !empty($stats['first_year']))
            <p class="text-slate-300 text-sm sm:text-base mt-1">
              {{ $user->profile->diveLevel->name ?? 'Uncertified Diver' }}
              @if($user->profile->diveLevel && !empty($stats['first_year']))
                <span class="mx-1 text-slate-600">·</span>
              @endif
              @if(!empty($stats['first_year'])) Diving since {{ $stats['first_year'] }} @endif
            </p>
          @endif

          {{-- Stats summary --}}
          @if(!empty($stats['total_dives']) || !empty($stats['total_hours']))
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
              <span class="text-cyan-400 font-semibold">{{ $stats['total_dives'] ?? 0 }}</span> dives
              @if(!empty($stats['total_hours']))
                · <span class="text-cyan-400 font-semibold">{{ $stats['total_hours'] }}</span>h underwater
              @endif
            </p>
          @endif

          {{-- Optional bio (inline preview) --}}
          @if(!empty($user->profile->bio))
            <p class="text-slate-400 text-sm mt-3 italic leading-relaxed max-w-sm mx-auto sm:mx-0">
              “{{ Str::limit($user->profile->bio, 100) }}”
            </p>
          @endif
        </div>
      </div>

      {{-- Right: Button --}}
      <div class="flex-shrink-0 relative z-10">
        <a href="{{ route('profile.edit') }}"
          class="inline-flex items-center gap-2 rounded-full bg-cyan-600 hover:bg-cyan-500 
                  text-white px-5 py-2.5 text-sm font-semibold shadow-md 
                  transition-all duration-200 hover:shadow-cyan-500/20 active:scale-[0.98]">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" 
                  d="M15.232 5.232a2.5 2.5 0 013.536 3.536L7.5 20.036l-4.243.707.707-4.243L15.232 5.232z"/>
          </svg>
          Edit Profile
        </a>
      </div>
    </section>

    {{-- 🧭 Welcome / Stats --}}
    <x-dashboard.welcome :user="$user" :stats="$stats" />

    {{-- 🌏 Dive Map --}}
    @if(isset($mapSites) && $mapSites->count())
    <section>
      <h2 class="text-lg font-semibold text-cyan-400 mb-3">Your Dive Map</h2>
      <div id="diveMap"
           class="w-full h-80 rounded-2xl overflow-hidden border border-white/10 shadow-lg bg-slate-700/40 flex items-center justify-center">
        <p class="text-slate-400 text-sm" id="diveMapPlaceholder">Map loading...</p>
      </div>
      <p class="text-xs text-slate-400 mt-2">Showing {{ $mapSites->count() }} logged dive sites.</p>
    </section>
    @endif

    {{-- ⚙️ Gear & Bio --}}
    @if($user->profile->suit_type || $user->profile->tank_type || $user->profile->weight_used || $user->profile->preferred_dive_type || $user->profile->bio)
    <section class="grid sm:grid-cols-2 gap-6">
      @if($user->profile->suit_type || $user->profile->tank_type || $user->profile->weight_used || $user->profile->preferred_dive_type)
      <div>
        <h2 class="text-lg font-semibold text-cyan-400 mb-3">Your Gear Setup</h2>
        <ul class="space-y-2 text-sm text-slate-300">
          @if($user->profile->suit_type)<li><strong>Suit:</strong> {{ $user->profile->suit_type }}</li>@endif
          @if($user->profile->tank_type)<li><strong>Tank:</strong> {{ $user->profile->tank_type }}</li>@endif
          @if($user->profile->weight_used)<li><strong>Weight:</strong> {{ $user->profile->weight_used }} kg</li>@endif
          @if($user->profile->preferred_dive_type)<li><strong>Preferred Type:</strong> {{ $user->profile->preferred_dive_type }}</li>@endif
        </ul>
      </div>
      @endif

      @if($user->profile->bio)
      <div>
        <h2 class="text-lg font-semibold text-cyan-400 mb-3">About You</h2>
        <p class="text-slate-300 text-sm leading-relaxed">
          {{ $user->profile->bio }}
        </p>
      </div>
      @endif
    </section>
    @endif

    {{-- 🧾 Recent Dives --}}
    @if(!empty($recentDives) && $recentDives->count())
    <section>
      <h2 class="text-lg font-semibold text-cyan-400 mb-3">Recent Dives</h2>
      <div class="divide-y divide-white/10 border border-white/10 rounded-2xl overflow-hidden">
        @foreach($recentDives as $dive)
          <div class="flex items-center justify-between px-4 py-3 bg-white/5 hover:bg-white/10 transition">
            <div>
              <div class="font-medium text-slate-100">{{ $dive->site->name ?? 'Unknown Site' }}</div>
              <div class="text-xs text-slate-400">{{ optional($dive->dive_date)->format('M j, Y') ?? '—' }}</div>
            </div>
            <div class="text-sm text-cyan-300">
              {{ $dive->depth ?? 0 }}m · {{ $dive->duration ?? 0 }}min
            </div>
          </div>
        @endforeach
      </div>
    </section>
    @endif

  </div>
</div>

{{-- ChartJS --}}
@if(!empty($stats['total_dives']) && $stats['total_dives'] > 3)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const ctx = document.getElementById('depthChart');
  if (ctx) {
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['0–10m', '10–20m', '20–30m', '30m+'],
        datasets: [{
          data: [12, 28, 9, 3],
          backgroundColor: 'rgba(6,182,212,0.6)',
          borderRadius: 6,
        }]
      },
      options: {
        scales: {
          y: { beginAtZero: true, ticks: { color: '#9CA3AF' } },
          x: { ticks: { color: '#9CA3AF' } }
        },
        plugins: { legend: { display: false } }
      }
    });
  }
});
</script>
@endif

{{-- ChartJS --}}
@if(!empty($stats['total_dives']) && $stats['total_dives'] > 3)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const ctx = document.getElementById('depthChart');
  if (ctx) {
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['0–10m', '10–20m', '20–30m', '30m+'],
        datasets: [{
          data: [12, 28, 9, 3],
          backgroundColor: 'rgba(6,182,212,0.6)',
          borderRadius: 6,
        }]
      },
      options: {
        scales: {
          y: { beginAtZero: true, ticks: { color: '#9CA3AF' } },
          x: { ticks: { color: '#9CA3AF' } }
        },
        plugins: { legend: { display: false } }
      }
    });
  }
});
</script>
@endif
@endsection