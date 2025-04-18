@extends('layouts.vizzbud')

@section('content')
<section class="max-w-2xl mx-auto px-6 py-12">
    <h1 class="text-3xl font-bold text-white mb-6">➕ Log a Dive</h1>

    <form method="POST" action="{{ route('logbook.store') }}" class="space-y-4 bg-slate-800 p-6 rounded-xl shadow">
        @csrf

        <label class="block">
            <span class="block mb-1 text-sm">Dive Site</span>
            <select name="dive_site_id" class="w-full rounded p-2 text-black">
                <option value="">-- Select site --</option>
                @foreach ($sites as $site)
                    <option value="{{ $site->id }}">{{ $site->name }}</option>
                @endforeach
            </select>
        </label>

        <label class="block">
            <span class="block mb-1 text-sm">Dive Date & Time</span>
            <input type="datetime-local" name="dive_date" required class="w-full rounded p-2 text-black">
        </label>

        <div class="grid grid-cols-2 gap-4">
            <label class="block">
                <span class="block mb-1 text-sm">Depth (m)</span>
                <input type="number" step="0.1" name="depth" class="w-full rounded p-2 text-black">
            </label>

            <label class="block">
                <span class="block mb-1 text-sm">Duration (mins)</span>
                <input type="number" name="duration" class="w-full rounded p-2 text-black">
            </label>
        </div>

        <!-- Expandable extras (optional) -->
        <details class="mt-4 bg-slate-700 rounded p-4">
            <summary class="cursor-pointer text-cyan-400 font-semibold">+ More dive details</summary>
            <div class="mt-4 grid gap-4">
                <input name="buddy" placeholder="Dive Buddy" class="rounded p-2 text-black">
                <input name="air_start" type="number" step="0.1" placeholder="Air Start (bar)" class="rounded p-2 text-black">
                <input name="air_end" type="number" step="0.1" placeholder="Air End (bar)" class="rounded p-2 text-black">
                <input name="temperature" type="number" step="0.1" placeholder="Water Temp °C" class="rounded p-2 text-black">
                <input name="suit_type" placeholder="Wetsuit/Drysuit Type" class="rounded p-2 text-black">
                <input name="tank_type" placeholder="Tank Type" class="rounded p-2 text-black">
                <input name="weight_used" placeholder="Weight Used (kg)" class="rounded p-2 text-black">
                <input name="visibility" type="number" step="0.1" placeholder="Visibility (m)" class="rounded p-2 text-black">
                <textarea name="notes" rows="3" placeholder="Notes" class="rounded p-2 text-black"></textarea>
                <label class="block text-sm">
                    Rating
                    <select name="rating" class="mt-1 w-full rounded p-2 text-black">
                        <option value="">—</option>
                        @for ($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}">{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
                        @endfor
                    </select>
                </label>
            </div>
        </details>

        <button type="submit" class="mt-6 bg-green-500 hover:bg-green-600 px-6 py-2 rounded font-semibold text-white">
            ✅ Save Dive Log
        </button>
    </form>
</section>
@endsection