@if ($logs->isEmpty())
    <p class="text-slate-400">No dives found for this search.</p>
@else
    <div class="overflow-x-auto bg-slate-800 rounded-xl shadow">
        <table class="min-w-full text-left text-sm text-slate-200">
            <thead class="bg-slate-900 text-slate-400">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Dive Site</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Depth</th>
                    <th class="px-4 py-3">Duration</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($logs as $index => $log)
                    <tr class="border-b border-slate-700 hover:bg-slate-700/40">
                        <td class="px-4 py-3">
                            {{ ($logs->currentPage() - 1) * $logs->perPage() + $index + 1 }}
                        </td>
                        <td class="px-4 py-3 font-medium">{{ $log->site->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ \Carbon\Carbon::parse($log->dive_date)->format('M j, Y') }}</td>
                        <td class="px-4 py-3">{{ $log->depth ? $log->depth . ' m' : '—' }}</td>
                        <td class="px-4 py-3">{{ $log->duration ? $log->duration . ' min' : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-6 text-sm text-slate-400">
        {{ $logs->links() }}
    </div>
@endif