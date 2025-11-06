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
<div class="min-h-screen bg-gradient-to-b from-slate-900 to-slate-800 text-slate-100 py-6 px-4 sm:px-6">
  <div class="max-w-6xl mx-auto space-y-10">

{{-- 🌊 Dashboard Hero --}}
<section
  class="relative isolate overflow-hidden rounded-2xl border border-white/10 
         bg-gradient-to-br from-slate-900/80 to-slate-800/70 
         p-6 sm:p-10 shadow-xl ring-1 ring-white/10 backdrop-blur-md">

  {{-- Cyan beam --}}
  <div class="absolute -inset-x-32 -top-40 h-72 
              bg-gradient-to-r from-cyan-500/20 via-transparent to-emerald-400/20 
              blur-3xl opacity-40"></div>

  <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
    
    {{-- Left: user info --}}
    <div class="flex items-center gap-4 sm:gap-6">
      <img 
        src="{{ $user->profile->avatar_url ?? asset('images/main/defaultProfile.webp') }}"
        alt="{{ $user->name }}"
        class="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-cover ring-2 ring-cyan-400/50 shadow-lg shadow-cyan-500/20">
      
      <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
          {{ $user->name }}
        </h1>

        {{-- Optional handle --}}
        @if(!empty($user->handle))
          <p class="text-sm text-cyan-400">{{ '@' . $user->handle }}</p>
        @endif

        {{-- Certification + first year --}}
        <p class="mt-1 text-sm text-slate-400">
          {{ $user->profile->diveLevel->name ?? 'Uncertified Diver' }}
          @if(!empty($stats['first_year']))
            · Diving since {{ $stats['first_year'] }}
          @endif
        </p>
      </div>
    </div>

    {{-- Right: key metrics --}}
    <div class="grid grid-cols-3 gap-3 sm:gap-6 text-center">
      <div class="flex flex-col">
        <span class="text-2xl sm:text-3xl font-bold text-cyan-400">{{ $stats['total_dives'] ?? 0 }}</span>
        <span class="text-xs sm:text-sm text-slate-400 uppercase tracking-wide">Dives</span>
      </div>
      <div class="flex flex-col">
        <span class="text-2xl sm:text-3xl font-bold text-emerald-400">{{ $stats['total_hours'] ?? 0 }}</span>
        <span class="text-xs sm:text-sm text-slate-400 uppercase tracking-wide">Hours</span>
      </div>
      <div class="flex flex-col">
        <span class="text-2xl sm:text-3xl font-bold text-cyan-300">{{ $stats['unique_sites'] ?? 0 }}</span>
        <span class="text-xs sm:text-sm text-slate-400 uppercase tracking-wide">Sites</span>
      </div>
    </div>
  </div>
</section>

    {{-- 🧭 Welcome / Stats --}}
    <x-dashboard.welcome :user="$user" :stats="$stats" />

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