@extends('layouts.vizzbud')

@section('title', 'Submit a Dive Report | Vizzbud')
@section('meta_description', 'Help the community by reporting your dive conditions — visibility, comments, and site data. Quick and easy on Vizzbud.')

@section('content')
<div class="max-w-xl mx-auto py-20 px-6">
    <h1 class="text-3xl font-bold mb-6 text-white">📝 Submit a Dive Report</h1>

    @if(session('success'))
        <div class="mb-4 text-green-400 font-medium">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('report.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm mb-1">Dive Site</label>
            <select name="dive_site_id" class="w-full rounded p-2 text-black" required>
                <option value="">Choose a site...</option>
                @foreach ($sites as $site)
                    <option value="{{ $site->id }}">{{ $site->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm mb-1">Visibility (meters)</label>
            <input type="number" step="0.1" name="viz_rating" class="w-full rounded p-2 text-black" />
        </div>

        <div>
            <label class="block text-sm mb-1">Comment</label>
            <textarea name="comment" rows="3" class="w-full rounded p-2 text-black"></textarea>
        </div>

        <div>
            <label class="block text-sm mb-1">Time of Dive</label>
            <input type="datetime-local" name="reported_at" class="w-full rounded p-2 text-black" required value="{{ now()->format('Y-m-d\TH:i') }}">
        </div>

        <button class="bg-cyan-500 hover:bg-cyan-600 px-6 py-2 rounded text-white font-semibold">Submit</button>
    </form>
</div>
@endsection