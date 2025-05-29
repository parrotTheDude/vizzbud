@extends('layouts.vizzbud')

@section('title', $diveSite->name . ' | Dive Site Info')
@section('meta_description', Str::limit(strip_tags($diveSite->description), 160))

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8 space-y-8 text-slate-800">

    {{-- H1 Title --}}
    <h1 class="text-4xl font-bold text-cyan-700">{{ $diveSite->name }}</h1>

    {{-- Static Map Preview --}}
    <div class="w-full h-72 rounded-xl overflow-hidden border">
        <iframe
            width="100%"
            height="100%"
            style="border:0;"
            loading="lazy"
            allowfullscreen
            referrerpolicy="no-referrer-when-downgrade"
            src="https://www.google.com/maps/embed/v1/place?key={{ config('services.google_maps.key') }}&q={{ $diveSite->lat }},{{ $diveSite->lng }}&zoom=13">
        </iframe>
    </div>

    {{-- Description --}}
    <div class="prose max-w-none">
        <p>{{ $diveSite->description }}</p>
    </div>

    {{-- Dive Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center text-sm">
        <div class="bg-slate-50 rounded-lg p-4 shadow-sm">
            <p class="font-semibold text-slate-500">Avg Depth</p>
            <p class="text-xl font-bold text-slate-800">{{ $diveSite->avg_depth }} m</p>
        </div>
        <div class="bg-slate-50 rounded-lg p-4 shadow-sm">
            <p class="font-semibold text-slate-500">Max Depth</p>
            <p class="text-xl font-bold text-slate-800">{{ $diveSite->max_depth }} m</p>
        </div>
        <div class="bg-slate-50 rounded-lg p-4 shadow-sm">
            <p class="font-semibold text-slate-500">Entry Type</p>
            <p class="text-xl font-bold text-slate-800 capitalize">{{ $diveSite->dive_type }}</p>
        </div>
        <div class="bg-slate-50 rounded-lg p-4 shadow-sm">
            <p class="font-semibold text-slate-500">Level</p>
            <p class="text-xl font-bold text-slate-800">{{ $diveSite->suitability }}</p>
        </div>
    </div>

    {{-- Current Conditions --}}
    @if($diveSite->latestCondition)
    <div class="mt-8">
        <h2 class="text-2xl font-semibold mb-4 text-cyan-700">🌊 Current Conditions</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 text-center text-sm">
            <div class="bg-white p-4 rounded-lg border">
                <p class="font-semibold text-slate-500">Wave Height</p>
                <p class="text-lg font-bold text-slate-800">{{ $diveSite->latestCondition->wave_height }} m</p>
            </div>
            <div class="bg-white p-4 rounded-lg border">
                <p class="font-semibold text-slate-500">Wave Period</p>
                <p class="text-lg font-bold text-slate-800">{{ $diveSite->latestCondition->wave_period }} s</p>
            </div>
            <div class="bg-white p-4 rounded-lg border">
                <p class="font-semibold text-slate-500">Wind</p>
                <p class="text-lg font-bold text-slate-800">
                    {{ number_format($diveSite->latestCondition->wind_speed * 1.94384, 1) }} kn<br>
                    from {{ \App\Helpers\CompassHelper::fromDegrees($diveSite->latestCondition->wind_direction) }}
                </p>
            </div>
            <div class="bg-white p-4 rounded-lg border">
                <p class="font-semibold text-slate-500">Water Temp</p>
                <p class="text-lg font-bold text-slate-800">{{ $diveSite->latestCondition->water_temperature }} °C</p>
            </div>
            <div class="bg-white p-4 rounded-lg border">
                <p class="font-semibold text-slate-500">Air Temp</p>
                <p class="text-lg font-bold text-slate-800">{{ $diveSite->latestCondition->air_temperature }} °C</p>
            </div>
        </div>
        <p class="text-xs text-slate-500 text-right mt-2">
            Last updated: {{ $diveSite->latestCondition->retrieved_at->diffForHumans() }}
        </p>
    </div>
    @endif

</div>
@endsection