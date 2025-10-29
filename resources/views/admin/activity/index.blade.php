@extends('layouts.vizzbud')

@section('title', 'Activity Logs | Admin | Vizzbud')
@section('meta_description', 'View and monitor user and system activity logs within the Vizzbud admin dashboard.')

@push('head')
  {{-- 🚫 Admin pages should never be indexed --}}
  <meta name="robots" content="noindex, nofollow">

  {{-- Canonical (for internal reference) --}}
  <link rel="canonical" href="{{ route('admin.activity.index') }}">

  {{-- Theme & display --}}
  <meta name="theme-color" content="#0f172a">
  <meta name="application-name" content="Vizzbud Admin">
  <meta name="color-scheme" content="dark">

  {{-- Optional structured data for internal admin clarity --}}
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "WebApplication",
    "name": "Vizzbud Admin — Activity Logs",
    "url": "{{ route('admin.activity.index') }}",
    "description": "Administrative dashboard page for viewing user and system activity logs on Vizzbud.",
    "creator": {
      "@type": "Organization",
      "name": "Vizzbud"
    }
  }
  </script>
@endpush

@section('content')
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-10 sm:py-12">

  {{-- Header --}}
  <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
      @if(isset($user))
        {{ $user->name }}’s Activity Logs
      @else
        Activity Logs
      @endif
    </h1>

    <div class="flex flex-wrap gap-2 w-full sm:w-auto">
      {{-- Back button changes depending on view --}}
      @if(isset($user))
        <a href="{{ route('admin.activity.index') }}"
          class="flex-1 sm:flex-none text-center inline-flex items-center justify-center gap-2 
                  text-sm font-semibold text-white/90 hover:text-white
                  bg-white/10 border border-white/10 ring-1 ring-white/10 rounded-full px-5 py-2.5
                  backdrop-blur-md hover:bg-white/15 transition-all duration-200 active:scale-[0.98]">
          ← All Logs
        </a>
      @else
        <a href="{{ route('admin.dashboard') }}"
          class="flex-1 sm:flex-none text-center inline-flex items-center justify-center gap-2 
                  text-sm font-semibold text-white/90 hover:text-white
                  bg-white/10 border border-white/10 ring-1 ring-white/10 rounded-full px-5 py-2.5
                  backdrop-blur-md hover:bg-white/15 transition-all duration-200 active:scale-[0.98]">
          ← Back
        </a>
      @endif

      <a href="{{ route('admin.activity.export', ['format' => 'json']) }}"
        class="flex-1 sm:flex-none text-center inline-flex items-center justify-center gap-2 
                text-sm font-semibold text-white/90 hover:text-white
                bg-cyan-600 rounded-full px-5 py-2.5 shadow-md
                hover:bg-cyan-500 transition-all duration-200 active:scale-[0.98]">
        Export JSON
      </a>

      <a href="{{ route('admin.activity.export', ['format' => 'csv']) }}"
        class="flex-1 sm:flex-none text-center inline-flex items-center justify-center gap-2 
                text-sm font-semibold text-white/90 hover:text-white
                bg-cyan-600 rounded-full px-5 py-2.5 shadow-md
                hover:bg-cyan-500 transition-all duration-200 active:scale-[0.98]">
        Export CSV
      </a>
    </div>
  </header>

  {{-- Summary Cards --}}
  <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
    <div class="p-4 rounded-xl bg-white/10 border border-white/10 ring-1 ring-white/10 text-white/90 text-center">
      <p class="text-sm text-white/60">Recent (24h)</p>
      <p class="text-2xl font-bold">{{ $summary['recent'] ?? 0 }}</p>
    </div>
    <div class="p-4 rounded-xl bg-white/10 border border-white/10 ring-1 ring-white/10 text-white/90 text-center">
      <p class="text-sm text-white/60">Active Users (7d)</p>
      <p class="text-2xl font-bold">{{ $summary['active_users'] ?? 0 }}</p>
    </div>
    <div class="p-4 rounded-xl bg-white/10 border border-white/10 ring-1 ring-white/10 text-white/90 text-center col-span-2 sm:col-span-1">
      <p class="text-sm text-white/60">Top Action</p>
      <p class="text-2xl font-bold text-cyan-300">{{ $summary['top_action']->action ?? '—' }}</p>
    </div>
  </div>

  {{-- Filters --}}
  @php
    $filtersUsed = request()->hasAny(['user','action','model','from','to','hide_self']);
  @endphp

  <form method="GET" class="w-full mb-8 bg-white/5 border border-white/10 ring-1 ring-white/10
                            backdrop-blur-md rounded-xl p-4 space-y-4">

    {{-- Filters Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
      {{-- User --}}
      <div>
        <label for="user" class="block text-sm text-white/70 mb-1">User</label>
        <input id="user" type="text" name="user" placeholder="Name or email"
               value="{{ request('user') }}"
               class="w-full rounded-lg bg-white/10 text-white px-3 py-2 border border-white/10 ring-1 ring-white/10 backdrop-blur-md
                      focus:ring-cyan-400/40 focus:border-cyan-400/50 transition"/>
      </div>

      {{-- Action --}}
      <div>
        <label for="action" class="block text-sm text-white/70 mb-1">Action</label>
        <input id="action" type="text" name="action" placeholder="e.g. login"
               value="{{ request('action') }}"
               class="w-full rounded-lg bg-white/10 text-white px-3 py-2 border border-white/10 ring-1 ring-white/10 backdrop-blur-md
                      focus:ring-cyan-400/40 focus:border-cyan-400/50 transition"/>
      </div>

      {{-- Model --}}
      <div>
        <label for="model" class="block text-sm text-white/70 mb-1">Model</label>
        <input id="model" type="text" name="model" placeholder="e.g. User"
               value="{{ request('model') }}"
               class="w-full rounded-lg bg-white/10 text-white px-3 py-2 border border-white/10 ring-1 ring-white/10 backdrop-blur-md
                      focus:ring-cyan-400/40 focus:border-cyan-400/50 transition"/>
      </div>

      {{-- From --}}
      <div>
        <label for="from" class="block text-sm text-white/70 mb-1">From</label>
        <input id="from" type="date" name="from" value="{{ request('from') }}"
               class="w-full rounded-lg bg-white/10 text-white px-3 py-2 border border-white/10 ring-1 ring-white/10 backdrop-blur-md
                      focus:ring-cyan-400/40 focus:border-cyan-400/50 transition"/>
      </div>

      {{-- To --}}
      <div>
        <label for="to" class="block text-sm text-white/70 mb-1">To</label>
        <input id="to" type="date" name="to" value="{{ request('to') }}"
               class="w-full rounded-lg bg-white/10 text-white px-3 py-2 border border-white/10 ring-1 ring-white/10 backdrop-blur-md
                      focus:ring-cyan-400/40 focus:border-cyan-400/50 transition"/>
      </div>

      {{-- Hide mine toggle --}}
      <div x-data="{ on: {{ request('hide_self', false) ? 'true' : 'false' }} }" class="flex flex-col justify-end">
        <label class="text-sm text-white/70 mb-1">Visibility</label>

        <label for="hide_self"
              class="flex items-center gap-3 cursor-pointer select-none h-[42px]"
              @click="on = !on">
          {{-- Switch --}}
          <div class="relative w-11 h-6 rounded-full border border-white/10 ring-1 ring-white/10 bg-white/10
                      transition-all duration-300"
              :class="on ? 'bg-cyan-500/40 ring-cyan-400/40' : ''">
            <div class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white
                        transition-all duration-300 shadow-sm"
                :class="on ? 'translate-x-5 bg-cyan-300' : 'bg-white/80'"></div>
          </div>

          {{-- Label text --}}
          <span class="text-sm transition-colors"
                :class="on ? 'text-cyan-300' : 'text-white/80'">
            Hide my logs
          </span>

          {{-- Hidden input to persist state on submit --}}
          <input type="hidden" name="hide_self" :value="on ? 1 : 0">
        </label>
      </div>
    </div>

    {{-- Buttons --}}
    <div class="flex flex-col sm:flex-row gap-3 mt-4">
      <button type="submit"
              class="flex-1 rounded-lg bg-cyan-500/90 hover:bg-cyan-400/90 text-white font-semibold px-6 py-2.5
                     border border-white/10 ring-1 ring-white/10 backdrop-blur-md transition shadow-md hover:shadow-lg">
        Apply Filters
      </button>

      @if($filtersUsed)
        <a href="{{ route('admin.activity.index') }}"
           class="flex-1 rounded-lg bg-white/10 hover:bg-white/15 text-white/90 hover:text-white font-semibold px-6 py-2.5
                  border border-white/10 ring-1 ring-white/10 backdrop-blur-md transition text-center">
          Reset
        </a>
      @endif
    </div>
  </form>

  {{-- Table (desktop) --}}
  <div class="hidden sm:block overflow-x-auto rounded-xl border border-white/10 ring-1 ring-white/10 backdrop-blur-md">
    <table class="min-w-full text-sm text-white/90">
      <thead class="bg-white/10 text-white/70 uppercase text-[0.75rem] tracking-wider">
        <tr>
          <th class="px-4 py-3 font-semibold text-left">User</th>
          <th class="px-4 py-3 font-semibold text-left">Action</th>
          <th class="px-4 py-3 font-semibold text-left">Model</th>
          <th class="px-4 py-3 font-semibold text-left">Metadata</th>
          <th class="px-4 py-3 font-semibold text-left">Time</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($logs as $log)
          <tr class="border-t border-white/10 hover:bg-white/5 transition">
            <td class="px-4 py-2 font-medium">
              @if($log->user_id)
                <a href="{{ route('admin.activity.user', $log->user_id) }}"
                   class="text-cyan-300 hover:underline">{{ $log->user->name ?? 'System' }}</a>
              @else
                <span class="text-white/70">System</span>
              @endif
            </td>
            <td class="px-4 py-2 font-semibold text-cyan-300">{{ Str::headline($log->action) }}</td>
            <td class="px-4 py-2">{{ class_basename($log->model_type ?? '-') }}</td>
            <td class="px-4 py-2 text-xs max-w-[320px] truncate">
              {{ Str::limit(json_encode($log->metadata, JSON_UNESCAPED_SLASHES), 120) }}
            </td>
            <td class="px-4 py-2 text-white/60">{{ $log->created_at->diffForHumans() }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="text-center py-6 text-white/60">No activity logs found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>

    {{-- Pagination --}}
    @if($logs instanceof \Illuminate\Pagination\LengthAwarePaginator)
      <div class="px-4 py-3 border-t border-white/10 bg-white/5">
        {{ $logs->withQueryString()->links() }}
      </div>
    @endif
  </div>

  {{-- Mobile Card View --}}
  <div class="sm:hidden space-y-4">
    @forelse ($logs as $log)
      <div class="rounded-xl bg-white/10 border border-white/10 ring-1 ring-white/10 p-4 space-y-2">
        <div class="flex justify-between items-center">
          <h3 class="font-semibold text-cyan-300">{{ Str::headline($log->action) }}</h3>
          <span class="text-xs text-white/60">{{ $log->created_at->diffForHumans() }}</span>
        </div>
        <p class="text-sm text-white/80">
          <strong>User:</strong>
          {{ $log->user->name ?? 'System' }}
        </p>
        <p class="text-sm text-white/70">
          <strong>Model:</strong> {{ class_basename($log->model_type ?? '-') }}
        </p>
        @if($log->metadata)
          <details class="mt-1">
            <summary class="text-sm text-cyan-300 cursor-pointer">View metadata</summary>
            <pre class="whitespace-pre-wrap text-xs bg-white/5 rounded p-2 text-white/80 mt-1 overflow-x-auto">
{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}
            </pre>
          </details>
        @endif
      </div>
    @empty
      <p class="text-center text-white/60">No activity logs found.</p>
    @endforelse

    <div class="pt-4">
      {{ $logs->links() }}
    </div>
  </div>

</section>
@endsection