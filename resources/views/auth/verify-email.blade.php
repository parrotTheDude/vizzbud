@extends('layouts.vizzbud')

@section('title', 'Verify Your Email | Vizzbud')
@section('meta_description', 'Please verify your email to activate your Vizzbud account and start logging your scuba dives and viewing live dive site data.')

@section('content')
@if (session('status') === 'verification-link-sent')
    <div 
        x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 5000)"
        x-show="show"
        x-transition
        class="bg-green-600 text-white px-4 py-2 rounded mb-4"
    >
        ✅ A new verification link has been sent to your email address.
    </div>
@endif
<section class="max-w-md mx-auto px-6 py-16 text-center">
    <h1 class="text-3xl font-bold text-white mb-6">📨 Verify Your Email</h1>
    <p class="text-slate-300 text-sm mb-6">
        Thanks for signing up! To activate your account, please verify your email address.
        We’ve sent a verification link to your inbox. Didn’t get it?
    </p>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="bg-cyan-500 hover:bg-cyan-600 text-white font-semibold py-2 px-4 rounded transition">
            🔁 Resend Verification Email
        </button>
    </form>

    <p class="mt-6 text-sm text-slate-400">
        Want to log in later?
        <a href="{{ route('logout') }}"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
           class="text-cyan-400 hover:underline">Log out</a>
    </p>

    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
        @csrf
    </form>
</section>
@endsection