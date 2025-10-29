@extends('layouts.vizzbud')

@section('title', 'Manage Dive Sites | Admin | Vizzbud')
@section('meta_description', 'Admin panel for managing all scuba dive sites in Vizzbud — create, edit, and organize global dive locations.')

@push('head')
  {{-- 🚫 Prevent indexing or external visibility --}}
  <meta name="robots" content="noindex, nofollow">

  {{-- Canonical link (useful for consistency, but not indexed) --}}
  <link rel="canonical" href="{{ route('admin.divesites.index') }}">

  {{-- Theme & PWA meta for smoother admin UX --}}
  <meta name="theme-color" content="#0f172a">
  <meta name="application-name" content="Vizzbud Admin">
  <meta name="color-scheme" content="dark">

  {{-- Optional internal JSON-LD (not for SEO, just structural clarity) --}}
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "WebApplication",
    "name": "Vizzbud Admin — Dive Site Manager",
    "url": "{{ route('admin.divesites.index') }}",
    "description": "Administrative dashboard page for managing Vizzbud dive site data.",
    "creator": {
      "@type": "Organization",
      "name": "Vizzbud"
    }
  }
  </script>
@endpush

@extends('layouts.vizzbud')

@section('title', 'Manage Dive Sites | Admin')

@section('content')
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-10 sm:py-12">

  {{-- ✅ Success Message --}}
  @if(session('success'))
    <div 
      x-data="{ show: true }"
      x-show="show"
      x-transition:enter="transition ease-out duration-500"
      x-transition:enter-start="opacity-0 -translate-y-4"
      x-transition:enter-end="opacity-100 translate-y-0"
      x-transition:leave="transition ease-in duration-500"
      x-transition:leave-start="opacity-100 translate-y-0"
      x-transition:leave-end="opacity-0 -translate-y-4"
      x-init="setTimeout(() => show = false, 4000)"
      class="mb-6 px-5 py-3 rounded-full bg-emerald-500/20 border border-emerald-400/30 
             text-emerald-200 text-sm font-medium text-center shadow-lg backdrop-blur-md"
    >
      {{ session('success') }}
    </div>
  @endif

  {{-- Header --}}
  <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-8">
    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
      Manage Dive Sites
    </h1>
    <a href="{{ route('admin.divesites.create') }}"
       class="inline-flex items-center justify-center gap-2 text-sm font-semibold text-white
              bg-cyan-600 px-5 py-2.5 rounded-full shadow-md hover:bg-cyan-500 
              active:scale-[0.98] transition-all duration-200">
      + Add New Dive Site
    </a>
  </header>

  {{-- Filters/Search --}}
  <form method="GET"
    class="mb-6 space-y-4 sm:space-y-0 sm:flex sm:items-center sm:justify-between sm:gap-3
          bg-white/5 border border-white/10 ring-1 ring-white/10 rounded-2xl p-4 sm:p-5 backdrop-blur-md">

    {{-- 🔍 Search Bar --}}
    <div class="w-full sm:flex-1">
      <label for="search" class="sr-only">Search</label>
      <input
        id="search"
        type="text"
        name="search"
        value="{{ request('search') }}"
        placeholder="Search by name, region, or country..."
        class="w-full rounded-full bg-white/10 text-white px-4 py-2.5 border border-white/10
              ring-1 ring-white/10 focus:ring-cyan-400/40 focus:border-cyan-400/50 transition placeholder-white/40"
      />
    </div>

    {{-- 📋 Filters + Actions --}}
    <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 w-full sm:w-auto">
      <div class="flex flex-wrap gap-3">
        <select name="status"
                class="flex-1 sm:flex-none min-w-[150px] rounded-full bg-white/10 border border-white/10 text-white px-4 py-2.5
                      focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
          <option value="">All statuses</option>
          <option value="review" @selected(request('status') === 'review')>Needs Review</option>
          <option value="active" @selected(request('status') === 'active')>Active</option>
          <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
        </select>
      </div>

      <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
        <button type="submit"
                class="w-full sm:w-auto rounded-full bg-cyan-500/90 hover:bg-cyan-400/90
                      text-white font-semibold px-6 py-2.5 border border-white/10
                      ring-1 ring-white/10 shadow-md hover:shadow-lg transition text-center">
          Apply Filters
        </button>

        @if(request()->hasAny(['search','status']))
          <a href="{{ route('admin.divesites.index') }}"
            class="w-full sm:w-auto rounded-full bg-white/10 hover:bg-white/15 text-white/80 hover:text-white
                    font-semibold px-6 py-2.5 border border-white/10 ring-1 ring-white/10 transition text-center">
            Reset
          </a>
        @endif
      </div>
    </div>
  </form>

  {{-- Dive Sites Table (desktop) --}}
  <div class="hidden sm:block overflow-x-auto rounded-2xl border border-white/10 ring-1 ring-white/10 backdrop-blur-md">
    <table class="min-w-full text-sm text-white/90">
      <thead class="bg-white/10 text-white/70 uppercase text-[0.75rem] tracking-wider">
        <tr>
          <th class="px-4 py-3 text-left font-semibold">Name</th>
          <th class="px-4 py-3 text-left font-semibold">Region</th>
          <th class="px-4 py-3 text-left font-semibold">Country</th>
          <th class="px-4 py-3 text-left font-semibold">Type</th>
          <th class="px-4 py-3 text-left font-semibold">Suitability</th>
          <th class="px-4 py-3 text-left font-semibold">Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($sites as $site)
          @php
              $statusLabel = $site->needs_review
                  ? 'Needs Review'
                  : ($site->is_active ? 'Active' : 'Inactive');

              $statusClass = $site->needs_review
                  ? 'bg-amber-500/30 text-amber-200 border border-amber-400/40'
                  : ($site->is_active
                      ? 'bg-emerald-500/30 text-emerald-200 border border-emerald-400/40'
                      : 'bg-rose-500/30 text-rose-200 border border-rose-400/40');
          @endphp

          <tr onclick='window.location="{{ route("admin.divesites.edit", $site->slug) }}"'
              class="border-t border-white/10 hover:bg-white/5 transition cursor-pointer">
            <td class="px-4 py-3 font-medium text-white">{{ $site->name }}</td>
            <td class="px-4 py-3 text-white/80">
              {{ optional($site->region)->name ?? '—' }}
              @if($site->region?->state)
                <span class="text-white/50 text-xs">
                  ({{ $site->region->state->name }})
                </span>
              @endif
            </td>
            <td class="px-4 py-3 text-white/80">{{ optional($site->region?->state?->country)->name ?? '—' }}</td>
            <td class="px-4 py-3 text-white/70">{{ ucfirst($site->dive_type ?? '—') }}</td>
            <td class="px-4 py-3 text-white/70">{{ $site->suitability ?? '—' }}</td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">
                {{ $statusLabel }}
              </span>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="text-center py-6 text-white/60">No dive sites found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>

    {{-- Pagination --}}
    @if($sites instanceof \Illuminate\Pagination\LengthAwarePaginator)
      <div class="px-4 py-3 border-t border-white/10 bg-white/5">
        {{ $sites->withQueryString()->links() }}
      </div>
    @endif
  </div>

  {{-- Mobile Card View --}}
  <div class="sm:hidden space-y-4">
    @forelse ($sites as $site)
      @php
          $statusLabel = $site->needs_review
              ? 'Needs Review'
              : ($site->is_active ? 'Active' : 'Inactive');

          $statusClass = $site->needs_review
              ? 'bg-amber-500/30 text-amber-200 border border-amber-400/40'
              : ($site->is_active
                  ? 'bg-emerald-500/30 text-emerald-200 border border-emerald-400/40'
                  : 'bg-rose-500/30 text-rose-200 border border-rose-400/40');
      @endphp
      <div onclick='window.location="{{ route("admin.divesites.edit", $site->slug) }}"'
           class="rounded-2xl bg-white/10 border border-white/10 ring-1 ring-white/10 p-4 cursor-pointer hover:bg-white/5 transition">
        <div class="flex justify-between items-center mb-2">
          <h3 class="text-lg font-semibold text-white">{{ $site->name }}</h3>
          <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">
            {{ $statusLabel }}
          </span>
        </div>
        <p class="text-sm text-white/80">
          <strong>Region:</strong> {{ optional($site->region)->name ?? '—' }}
        </p>
        <p class="text-sm text-white/80">
          <strong>Country:</strong> {{ optional($site->region?->state?->country)->name ?? '—' }}
        </p>
        <p class="text-sm text-white/80">
          <strong>Type:</strong> {{ ucfirst($site->dive_type ?? '—') }}
        </p>
        <p class="text-sm text-white/80">
          <strong>Suitability:</strong> {{ $site->suitability ?? '—' }}
        </p>
      </div>
    @empty
      <p class="text-center text-white/60">No dive sites found.</p>
    @endforelse

    @if($sites instanceof \Illuminate\Pagination\LengthAwarePaginator)
      <div class="pt-4">
        {{ $sites->withQueryString()->links() }}
      </div>
    @endif
  </div>

</section>
@endsection