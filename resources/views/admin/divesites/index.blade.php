@extends('layouts.vizzbud')

@section('title', 'Manage Dive Sites | Admin')

@section('content')
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-10 sm:py-12">

  {{-- Header --}}
  <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-8">
    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
      Manage Dive Sites
    </h1>
    <a href="{{ route('admin.divesites.create') }}"
       class="inline-flex items-center gap-2 text-sm font-semibold text-white/80 hover:text-white
              bg-cyan-600 px-4 py-2.5 rounded-full shadow-md hover:bg-cyan-500 transition">
      + Add New Dive Site
    </a>
  </header>

  {{-- Filters/Search --}}
  <form method="GET" class="mb-6 flex flex-wrap gap-3 bg-white/5 border border-white/10 ring-1 ring-white/10 rounded-xl p-4 backdrop-blur-md">
    <div class="flex-1 min-w-[200px]">
      <input type="text" name="search" value="{{ request('search') }}"
            placeholder="Search by name, region, or country..."
            class="w-full rounded-lg bg-white/10 text-white px-3 py-2 border border-white/10 
                    ring-1 ring-white/10 focus:ring-cyan-400/40 focus:border-cyan-400/50 transition"/>
    </div>

    <div class="flex items-center gap-3 flex-wrap">
      <select name="status"
              class="rounded-lg bg-white/10 border border-white/10 text-white px-3 py-2
                    focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
        <option value="">All statuses</option>
        <option value="review" @selected(request('status') === 'review')>Needs Review</option>
        <option value="active" @selected(request('status') === 'active')>Active</option>
        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
      </select>

      <button type="submit"
              class="rounded-lg bg-cyan-500/90 hover:bg-cyan-400/90
                    text-white font-semibold px-5 py-2 border border-white/10
                    ring-1 ring-white/10 shadow-md hover:shadow-lg transition">
        Apply
      </button>

      @if(request()->hasAny(['search','status']))
        <a href="{{ route('admin.divesites.index') }}"
          class="rounded-lg bg-white/10 hover:bg-white/15 text-white/80 font-semibold px-5 py-2
                  border border-white/10 ring-1 ring-white/10 transition">
          Reset
        </a>
      @endif
    </div>
  </form>

  {{-- Dive Sites Table --}}
  <div class="overflow-x-auto rounded-xl border border-white/10 ring-1 ring-white/10 backdrop-blur-md">
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
            <td class="px-4 py-3 text-white/80">{{ $site->region ?? '—' }}</td>
            <td class="px-4 py-3 text-white/80">{{ $site->country ?? '—' }}</td>
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
</section>
@endsection