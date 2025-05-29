@extends('layouts.vizzbud')

@section('title', $diveSite->name . ' | Dive Site Info')
@section('meta_description', Str::limit(strip_tags($diveSite->description), 160))

@php use App\Helpers\CompassHelper; @endphp

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8 space-y-8 text-slate-800">

    {{-- H1 Title --}}
    <h1 class="text-4xl font-bold text-cyan-700">{{ $diveSite->name }}</h1>

    {{-- Static Mapbox Preview --}}
    <div id="siteMap" class="w-full h-72 rounded-xl overflow-hidden border"></div>

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
        <h2 class="text-2xl font-semibold mb-4 text-cyan-700">