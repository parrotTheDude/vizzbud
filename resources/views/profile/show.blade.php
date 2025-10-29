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
    <section class="flex flex-col sm:flex-row items-center sm:items-end justify-between gap-6">
      <div class="flex items-center gap-4">
        <img src="{{ $user->avatar_url ?? asset('images/main/defaultProfile.webp') }}"
          alt="Profile photo"
          class="w-24 h-24 rounded-full border-4 border-cyan-400/40 shadow-md object-cover">
        <div>
          <h1 class="text-2xl font-bold text-cyan-400">{{ $user->name }}</h1>

          @if($user->certification || !empty($stats['first_year']))
            <p class="text-slate-300 text-sm">
              {{ $user->certification ?? '' }}
              @if($user->certification && !empty($stats['first_year'])) <span class="mx-1 text-slate-500">·</span> @endif
              @if(!empty($stats['first_year'])) Diving since {{ $stats['first_year'] }} @endif
            </p>
          @endif

          @if(!empty($stats['total_dives']) || !empty($stats['total_hours']))
            <p class="text-xs text-slate-400 mt-1">
              {{ $stats['total_dives'] ?? 0 }} dives
              @if(!empty($stats['total_hours'])) · {{ $stats['total_hours'] }}h underwater @endif
            </p>
          @endif
        </div>
      </div>

      <a href="{{ route('profile.edit') }}"
         class="rounded-full bg-cyan-600 px-5 py-2 text-sm font-semibold hover:bg-cyan-500 transition">
         Edit Profile
      </a>
    </section>

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

    {{-- 📊 Dive Insights --}}
    @if(!empty($stats['total_dives']) && $stats['total_dives'] > 3)
    <section>
      <h2 class="text-lg font-semibold text-cyan-400 mb-4">Dive Insights</h2>
      <div class="grid sm:grid-cols-3 gap-4">
        <div class="rounded-2xl bg-white/10 backdrop-blur-xl border border-white/20 p-4">
          <h3 class="text-sm font-semibold text-slate-200 mb-2">Depth Distribution</h3>
          <canvas id="depthChart" class="w-full h-40"></canvas>
        </div>
        <div class="rounded-2xl bg-white/10 backdrop-blur-xl border border-white/20 p-4">
          <h3 class="text-sm font-semibold text-slate-200 mb-2">Dive Frequency</h3>
          <canvas id="frequencyChart" class="w-full h-40"></canvas>
        </div>
        <div class="rounded-2xl bg-white/10 backdrop-blur-xl border border-white/20 p-4">
          <h3 class="text-sm font-semibold text-slate-200 mb-2">Dive Type</h3>
          <canvas id="typeChart" class="w-full h-40"></canvas>
        </div>
      </div>
    </section>
    @endif

    {{-- 🥇 Achievements --}}
    @if(!empty($badges) && count($badges))
    <section>
      <h2 class="text-lg font-semibold text-cyan-400 mb-3">Achievements</h2>
      <div class="flex flex-wrap gap-3">
        @foreach($badges as $badge)
          <div class="flex items-center gap-2 bg-white/10 border border-white/20 px-3 py-2 rounded-full text-sm shadow-sm">
            <img src="{{ $badge['icon'] ?? asset('images/icons/star.svg') }}" class="w-5 h-5" alt="">
            <span class="text-slate-200">{{ $badge['label'] ?? 'Achievement' }}</span>
          </div>
        @endforeach
      </div>
    </section>
    @endif

    {{-- ⚙️ Gear & Bio --}}
    @if($user->suit_type || $user->tank_type || $user->weight_used || $user->preferred_dive_type || $user->bio)
    <section class="grid sm:grid-cols-2 gap-6">
      @if($user->suit_type || $user->tank_type || $user->weight_used || $user->preferred_dive_type)
      <div>
        <h2 class="text-lg font-semibold text-cyan-400 mb-3">Your Gear Setup</h2>
        <ul class="space-y-2 text-sm text-slate-300">
          @if($user->suit_type)<li><strong>Suit:</strong> {{ $user->suit_type }}</li>@endif
          @if($user->tank_type)<li><strong>Tank:</strong> {{ $user->tank_type }}</li>@endif
          @if($user->weight_used)<li><strong>Weight:</strong> {{ $user->weight_used }} kg</li>@endif
          @if($user->preferred_dive_type)<li><strong>Preferred Type:</strong> {{ $user->preferred_dive_type }}</li>@endif
        </ul>
      </div>
      @endif

      @if($user->bio)
      <div>
        <h2 class="text-lg font-semibold text-cyan-400 mb-3">About You</h2>
        <p class="text-slate-300 text-sm leading-relaxed">
          {{ $user->bio }}
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
@endsection