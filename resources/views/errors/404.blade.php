@extends('layouts.vizzbud')

@section('title', 'Page Not Found | Vizzbud')
@section('meta_description', 'Oops! The page you’re looking for doesn’t exist. Return to the map or explore dive sites on Vizzbud.')

@section('content')
<section class="max-w-2xl mx-auto text-center px-6 py-24">
    <h1 class="text-6xl font-bold text-white mb-6">404</h1>
    <p class="text-slate-300 text-lg mb-6">Oops! We couldn’t find that page.</p>
    <a href="{{ route('home') }}" class="bg-cyan-500 hover:bg-cyan-600 text-white font-semibold py-3 px-6 rounded transition">
        ⛵ Back to Home
    </a>
</section>
@endsection