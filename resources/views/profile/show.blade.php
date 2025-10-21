@extends('layouts.vizzbud')

@section('title', $user->name . ' | Profile | Vizzbud')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-slate-900 to-slate-800 text-slate-100 py-10 px-4 sm:px-6">
  <div class="max-w-6xl mx-auto space-y-10">

    <!-- 🧍 Diver Header -->
    <section class="flex flex-col sm:flex-row items-center sm:items-end justify-between gap-6">
      <div class="flex items-center gap-4">
        <img src="{{ $user->avatar_url ?? '/images/default-avatar.webp' }}"
             alt="Profile photo"
             class="w-24 h-24 rounded-full border-4 border-cyan-400/40 shadow-md object-cover">
        <div>
          <h1 class="text-2xl font-bold text-cyan-400">{{ $user->name }}</h1>
          <p class="text-slate-300 text-sm">
            {{ $user->certification ?? 'Open Water Diver' }} ·
            Diving since {{ $stats['first_year'] ?? '—' }}
          </p>
          <p class="text-xs text-slate-400 mt-1">
            {{ $stats['total_dives'] ?? 0 }} dives · {{ $stats['total_hours'] ?? '0h' }} underwater
          </p>
        </div>
      </div>
      <a href="{{ route('profile.edit') }}"
         class="rounded-full bg-cyan-600 px-5 py-2 text-sm font-semibold hover:bg-cyan-500 transition">
         Edit Profile
      </a>
    </section>

    <!-- 🌏 Dive Map -->
    <section>
      <h2 class="text-lg font-semibold text-cyan-400 mb-3">Your Dive Map</h2>
      <div id="diveMap" class="w-full h-80 rounded-2xl overflow-hidden border border-white/10 shadow-lg bg-slate-700/40">
        <!-- Mapbox or Leaflet will render here -->
      </div>
      <p class="text-xs text-slate-400 mt-2">Showing {{ count($mapSites ?? []) }} logged dive sites.</p>
    </section>

    <!-- 📊 Dive Insights -->
    <section>
      <h2 class="text-lg font-semibold text-cyan-400 mb-4">Dive Insights</h2>
      <div class="grid sm:grid-cols-3 gap-4">
        <!-- Depth Distribution -->
        <div class="rounded-2xl bg-white/10 backdrop-blur-xl border border-white/20 p-4">
          <h3 class="text-sm font-semibold text-slate-200 mb-2">Depth Distribution</h3>
          <canvas id="depthChart" class="w-full h-40"></canvas>
        </div>

        <!-- Dive Frequency -->
        <div class="rounded-2xl bg-white/10 backdrop-blur-xl border border-white/20 p-4">
          <h3 class="text-sm font-semibold text-slate-200 mb-2">Dive Frequency</h3>
          <canvas id="frequencyChart" class="w-full h-40"></canvas>
        </div>

        <!-- Dive Type -->
        <div class="rounded-2xl bg-white/10 backdrop-blur-xl border border-white/20 p-4">
          <h3 class="text-sm font-semibold text-slate-200 mb-2">Dive Type</h3>
          <canvas id="typeChart" class="w-full h-40"></canvas>
        </div>
      </div>
    </section>

    <!-- 🥇 Achievements -->
    <section>
      <h2 class="text-lg font-semibold text-cyan-400 mb-3">Achievements</h2>
      <div class="flex flex-wrap gap-3">
        @foreach($badges ?? [] as $badge)
          <div class="flex items-center gap-2 bg-white/10 border border-white/20 px-3 py-2 rounded-full text-sm shadow-sm">
            <img src="{{ $badge['icon'] }}" class="w-5 h-5" alt="">
            <span class="text-slate-200">{{ $badge['label'] }}</span>
          </div>
        @endforeach

        @if(empty($badges))
          <p class="text-slate-400 text-sm">Log more dives to earn your first badge!</p>
        @endif
      </div>
    </section>

    <!-- ⚙️ Gear & Bio -->
    <section class="grid sm:grid-cols-2 gap-6">
      <div>
        <h2 class="text-lg font-semibold text-cyan-400 mb-3">Your Gear Setup</h2>
        <ul class="space-y-2 text-sm text-slate-300">
          <li><strong>Suit:</strong> {{ $user->suit_type ?? '—' }}</li>
          <li><strong>Tank:</strong> {{ $user->tank_type ?? '—' }}</li>
          <li><strong>Weight:</strong> {{ $user->weight_used ? $user->weight_used . ' kg' : '—' }}</li>
          <li><strong>Preferred Type:</strong> {{ $user->preferred_dive_type ?? '—' }}</li>
        </ul>
      </div>

      <div>
        <h2 class="text-lg font-semibold text-cyan-400 mb-3">About You</h2>
        <p class="text-slate-300 text-sm leading-relaxed">
          {{ $user->bio ?? 'Tell others a bit about your diving experience, favourite sites, or goals!' }}
        </p>
      </div>
    </section>

    <!-- 🧾 Recent Dives (condensed) -->
    @if(!empty($recentDives))
    <section>
      <h2 class="text-lg font-semibold text-cyan-400 mb-3">Recent Dives</h2>
      <div class="divide-y divide-white/10 border border-white/10 rounded-2xl overflow-hidden">
        @foreach($recentDives as $dive)
          <div class="flex items-center justify-between px-4 py-3 bg-white/5 hover:bg-white/10 transition">
            <div>
              <div class="font-medium text-slate-100">{{ $dive->site->name }}</div>
              <div class="text-xs text-slate-400">{{ $dive->dive_date?->format('M j, Y') ?? '—' }}</div>
            </div>
            <div class="text-sm text-cyan-300">{{ $dive->depth }}m · {{ $dive->duration }}min</div>
          </div>
        @endforeach
      </div>
    </section>
    @endif

  </div>
</div>

<!-- ChartJS (if not already globally loaded) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Example dummy chart setup — replace with your real data later
  new Chart(document.getElementById('depthChart'), {
    type: 'bar',
    data: {
      labels: ['0–10m', '10–20m', '20–30m', '30m+'],
      datasets: [{ data: [12, 28, 9, 3], backgroundColor: 'rgba(6,182,212,0.6)' }]
    },
    options: { plugins: { legend: { display: false } } }
  });
});
</script>
@endsection