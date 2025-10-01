@if ($logs->isEmpty())
  <div class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-white/10 backdrop-blur-xl shadow-xl p-8 text-center">
    <p class="text-slate-300">No dives found for this search.</p>
  </div>
@else
  <div class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-white/10 backdrop-blur-xl shadow-xl overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        {{-- Sticky, glassy header --}}
        <thead class="sticky top-0 z-10 bg-slate-900/60 backdrop-blur supports-[backdrop-filter]:backdrop-blur-md text-slate-300">
          <tr class="[&>th]:px-4 [&>th]:py-3 [&>th]:font-semibold [&>th]:text-left">
            <th class="w-12">#</th>
            <th>Dive Site</th>
            <th>Date</th>
            <th>Depth</th>
            <th>Duration</th>
            <th class="w-8"></th>
          </tr>
        </thead>

        <tbody class="text-slate-200">
          @foreach ($logs as $index => $log)
            @php
              $rowNum = ($logs->currentPage() - 1) * $logs->perPage() + $index + 1;
              $date   = \Carbon\Carbon::parse($log->dive_date)->format('M j, Y');
              $depth  = $log->depth ? $log->depth . ' m' : '—';
              $dur    = $log->duration ? $log->duration . ' min' : '—';
            @endphp

            <tr class="group border-b border-white/10 hover:bg-white/5 focus-within:bg-white/5 transition">
              <td class="px-4 py-3 tabular-nums text-slate-400">{{ $rowNum }}</td>

              {{-- Dive Site (row link lives here for a11y + full-row click) --}}
              <td class="px-4 py-3 font-semibold">
                <a href="{{ route('logbook.show', $log) }}"
                   class="flex items-center gap-2 outline-none focus-visible:ring-2 focus-visible:ring-cyan-300 rounded-md">
                  @include('components.icon', ['name' => 'map-pin'])
                  <span class="truncate">{{ $log->site->name ?? '—' }}</span>
                </a>
              </td>

              {{-- Date --}}
              <td class="px-4 py-3">
                <span class="inline-flex items-center gap-2 text-slate-300">
                  @include('components.icon', ['name' => 'calendar'])
                  <span>{{ $date }}</span>
                </span>
              </td>

              {{-- Depth (pill) --}}
              <td class="px-4 py-3">
                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                              bg-white/10 ring-1 ring-white/15 border border-white/10">
                  @include('components.icon', ['name' => 'depth'])
                  <span class="ml-1 tabular-nums">{{ $depth }}</span>
                </span>
              </td>

              {{-- Duration (pill) --}}
              <td class="px-4 py-3">
                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                              bg-white/10 ring-1 ring-white/15 border border-white/10">
                  @include('components.icon', ['name' => 'timer'])
                  <span class="ml-1 tabular-nums">{{ $dur }}</span>
                </span>
              </td>

              {{-- Chevron affordance --}}
              <td class="px-2 py-3">
                <a href="{{ route('logbook.show', $log) }}"
                   class="flex h-8 w-8 items-center justify-center rounded-md text-cyan-300/70
                          group-hover:text-cyan-300 outline-none focus-visible:ring-2 focus-visible:ring-cyan-300">
                  @include('components.icon', ['name' => 'chevron-right'])
                </a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    {{-- Pagination footer --}}
    <div class="px-4 py-3 border-t border-white/10 bg-slate-900/40 text-sm text-slate-300">
      {{ $logs->links() }}
    </div>
  </div>
@endif