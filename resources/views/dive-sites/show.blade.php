@extends('layouts.vizzbud')

@section('title', $diveSite->name . ' | Vizzbud')
@section('description', Str::limit(strip_tags($diveSite->description), 150))

@section('content')
<section class="max-w-4xl mx-auto px-6 py-12 space-y-8 text-white">

    <a href="{{ route('dive-sites.index') }}" class="text-cyan-400 hover:underline">← Back to All Dive Sites</a>

    <h1 class="text-4xl font-bold">{{ $diveSite->name }}</h1>

    @if($diveSite->description)
        <p class="text-slate-300">{{ $diveSite->description }}</p>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 bg-slate-800 p-6 rounded-xl shadow">
        <div>
            <h2 class="text-xl font-semibold">📍 Location</h2>
            <p>{{ $diveSite->region }}, {{ $diveSite->country }}</p>
            <p class="text-sm text-slate-400">Lat: {{ $diveSite->lat }}, Lng: {{ $diveSite->lng }}</p>
        </div>

        <div>
            <h2 class="text-xl font-semibold">📊 Site Info</h2>
            <ul class="text-slate-300">
                <li>Max Depth: {{ $diveSite->max_depth }} m</li>
                <li>Avg Depth: {{ $diveSite->avg_depth }} m</li>
                <li>Dive Type: {{ ucfirst($diveSite->dive_type) }}</li>
                <li>Suitability: {{ ucfirst($diveSite->suitability) }}</li>
            </ul>
        </div>
    </div>

    @if ($diveSite->latestCondition)
        <div class="bg-slate-700 p-6 rounded-xl">
            <h2 class="text-xl font-semibold mb-2">🌊 Latest Conditions</h2>
            <ul class="text-slate-300">
                <li>Wind: {{ $diveSite->latestCondition->wind_speed_knots }} knots ({{ $diveSite->latestCondition->wind_direction }})</li>
                <li>Wave Height: {{ $diveSite->latestCondition->wave_height }} m</li>
                <li>Status: {{ ucfirst($diveSite->latestCondition->status) }}</li>
                <li>As of: {{ $diveSite->latestCondition->retrieved_at->format('M d, H:i') }}</li>
            </ul>
        </div>
    @endif

    @if ($diveSite->diveLogs->count())
        <div>
            <h2 class="text-xl font-semibold mb-2">📝 Recent Logs</h2>
            <ul class="space-y-2">
                @foreach ($diveSite->diveLogs->sortByDesc('dive_date')->take(3) as $log)
                    <li class="bg-slate-700 rounded p-4">
                        <p><strong>{{ $log->dive_date->format('M d, Y') }}</strong> — {{ $log->depth }}m for {{ $log->duration }}min</p>
                        @if($log->notes)
                            <p class="text-sm text-slate-300 mt-1">{{ Str::limit($log->notes, 100) }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

</section>
@endsection