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
  <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-8">
    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
      Activity Logs
    </h1>
    <div class="flex flex-wrap gap-2">
      <a href="{{ route('admin.dashboard') }}"
         class="inline-flex items-center gap-2 text-sm font-semibold text-white/80 hover:text-white
                bg-white/10 border border-white/10 ring-1 ring-white/10 rounded-full px-4 py-2 backdrop-blur-md
                hover:bg-white/15 transition">
        ← Back to Dashboard
      </a>
      <a href="{{ route('admin.activity.export', ['format' => 'json']) }}"
         class="inline-flex items-center gap-2 text-sm font-semibold text-white/80 hover:text-white
                bg-white/10 border border-white/10 ring-1 ring-white/10 rounded-full px-4 py-2 backdrop-blur-md
                hover:bg-white/15 transition">
        Export JSON
      </a>
      <a href="{{ route('admin.activity.export', ['format' => 'csv']) }}"
         class="inline-flex items-center gap-2 text-sm font-semibold text-white/80 hover:text-white
                bg-white/10 border border-white/10 ring-1 ring-white/10 rounded-full px-4 py-2 backdrop-blur-md
                hover:bg-white/15 transition">
        Export CSV
      </a>
    </div>
  </header>

  {{-- Summary cards --}}
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="p-4 rounded-xl bg-white/10 border border-white/10 ring-1 ring-white/10 text-white/90">
      <p class="text-sm text-white/60">Recent (24h)</p>
      <p class="text-2xl font-bold">{{ $summary['recent'] ?? 0 }}</p>
    </div>
    <div class="p-4 rounded-xl bg-white/10 border border-white/10 ring-1 ring-white/10 text-white/90">
      <p class="text-sm text-white/60">Active Users (7d)</p>
      <p class="text-2xl font-bold">{{ $summary['active_users'] ?? 0 }}</p>
    </div>
    <div class="p-4 rounded-xl bg-white/10 border border-white/10 ring-1 ring-white/10 text-white/90">
      <p class="text-sm text-white/60">Top Action</p>
      <p class="text-2xl font-bold text-cyan-300">{{ $summary['top_action']->action ?? '—' }}</p>
    </div>
  </div>

  {{-- Filters --}}
  @php
    $filtersUsed = request()->hasAny(['user','action','model','from','to','include_self']);
  @endphp

  <form method="GET"
        class="w-full mb-6 bg-white/5 border border-white/10 ring-1 ring-white/10
              backdrop-blur-md rounded-xl p-4">

    {{-- Filters Row --}}
    <div class="flex flex-wrap gap-3 mb-4">

      {{-- User --}}
      <div class="flex flex-col flex-1 min-w-[180px] text-white/80 text-sm">
        <label for="user" class="mb-1">User</label>
        <input id="user" type="text" name="user" placeholder="Name or email"
              value="{{ request('user') }}"
              class="rounded-lg bg-white/10 text-white px-3 py-2 h-[42px]
                      border border-white/10 ring-1 ring-white/10 backdrop-blur-md
                      focus:ring-cyan-400/40 focus:border-cyan-400/50 transition w-full"/>
      </div>

      {{-- Action --}}
      <div class="flex flex-col flex-1 min-w-[140px] text-white/80 text-sm">
        <label for="action" class="mb-1">Action</label>
        <input id="action" type="text" name="action" placeholder="e.g. login"
              value="{{ request('action') }}"
              class="rounded-lg bg-white/10 text-white px-3 py-2 h-[42px]
                      border border-white/10 ring-1 ring-white/10 backdrop-blur-md
                      focus:ring-cyan-400/40 focus:border-cyan-400/50 transition w-full"/>
      </div>

      {{-- Model --}}
      <div class="flex flex-col flex-1 min-w-[140px] text-white/80 text-sm">
        <label for="model" class="mb-1">Model</label>
        <input id="model" type="text" name="model" placeholder="e.g. User"
              value="{{ request('model') }}"
              class="rounded-lg bg-white/10 text-white px-3 py-2 h-[42px]
                      border border-white/10 ring-1 ring-white/10 backdrop-blur-md
                      focus:ring-cyan-400/40 focus:border-cyan-400/50 transition w-full"/>
      </div>

      {{-- From --}}
      <div class="flex flex-col flex-1 min-w-[140px] text-white/80 text-sm">
        <label for="from" class="mb-1">From</label>
        <input id="from" type="date" name="from" value="{{ request('from') }}"
              class="rounded-lg bg-white/10 text-white px-3 py-2 h-[42px]
                      border border-white/10 ring-1 ring-white/10 backdrop-blur-md
                      focus:ring-cyan-400/40 focus:border-cyan-400/50 transition w-full"/>
      </div>

      {{-- To --}}
      <div class="flex flex-col flex-1 min-w-[140px] text-white/80 text-sm">
        <label for="to" class="mb-1">To</label>
        <input id="to" type="date" name="to" value="{{ request('to') }}"
              class="rounded-lg bg-white/10 text-white px-3 py-2 h-[42px]
                      border border-white/10 ring-1 ring-white/10 backdrop-blur-md
                      focus:ring-cyan-400/40 focus:border-cyan-400/50 transition w-full"/>
      </div>

      {{-- Hide my logs toggle --}}
      <div x-data="{ hideMine: {{ request('hide_self') ? 'true' : 'false' }} }"
          class="flex flex-col flex-1 min-w-[160px] text-white/80 text-sm justify-end">
        <label for="hide_self" class="mb-1">Visibility</label>
        <div class="flex items-center gap-3 h-[42px]">
          <span class="whitespace-nowrap text-white/80">Hide my logs</span>
          <button type="button"
                  @click="hideMine = !hideMine; $refs.input.checked = hideMine"
                  :class="hideMine ? 'bg-cyan-500/90' : 'bg-white/15'"
                  class="relative inline-flex h-6 w-11 rounded-full transition-colors duration-300 border border-white/20 flex-shrink-0 focus:outline-none">
            <span :class="hideMine ? 'translate-x-5 bg-white' : 'translate-x-0.5 bg-white/70'"
                  class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full shadow-sm transform transition duration-300 ease-in-out"></span>
          </button>
          <input type="checkbox" name="hide_self" value="1" x-ref="input"
                class="hidden" @checked(request('hide_self'))>
        </div>
      </div>
    </div>

    {{-- Buttons Row --}}
    <div class="flex flex-col sm:flex-row gap-3 mt-4 w-full">
    <button type="submit"
            class="flex-1 rounded-lg bg-cyan-500/90 hover:bg-cyan-400/90
                    text-white font-semibold px-6 py-2.5 h-[44px]
                    border border-white/10 ring-1 ring-white/10 backdrop-blur-md
                    transition shadow-md hover:shadow-lg text-center">
        Apply Filters
    </button>

    @if($filtersUsed)
        <a href="{{ route('admin.activity.index') }}"
        class="flex-1 rounded-lg bg-white/10 hover:bg-white/15
                text-white/90 hover:text-white font-semibold px-6 py-2.5 h-[44px]
                border border-white/10 ring-1 ring-white/10 backdrop-blur-md
                transition text-center">
        Reset
        </a>
    @endif
    </div>
    </form>

  {{-- Table --}}
  <div class="overflow-x-auto rounded-xl border border-white/10 ring-1 ring-white/10 backdrop-blur-md">
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
            <td class="px-4 py-2 font-semibold text-cyan-300">
              {{ Str::headline(str_replace('_', ' ', $log->action)) }}
            </td>
            <td class="px-4 py-2">{{ class_basename($log->model_type ?? '-') }}</td>
            <td class="px-4 py-2 text-xs max-w-[320px]">
              <div x-data="{ open: false }">
                <code x-show="!open" @click="open = true" class="cursor-pointer text-white/60 hover:text-white">
                  {{ Str::limit(json_encode($log->metadata, JSON_UNESCAPED_SLASHES), 120) }}
                </code>
                <pre x-show="open"
                     class="whitespace-pre-wrap bg-white/5 rounded p-2 text-[0.7rem] overflow-x-auto text-white/80">
{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}
                </pre>
              </div>
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
</section>
@endsection