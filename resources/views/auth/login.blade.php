@extends('layouts.vizzbud')

@section('title', 'Log In to Vizzbud')
@section('meta_description', 'Access your Vizzbud dive log account to manage your dives, view stats, and explore scuba sites with real-time conditions.')

@section('content')
<section class="max-w-md mx-auto px-6 py-16">
    <h1 class="text-3xl font-bold text-white mb-6 text-center">🔐 Log in to Vizzbud</h1>

    @if (session('status'))
        <div class="bg-green-600 text-white px-4 py-2 rounded mb-4">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="bg-slate-800 rounded-xl p-6 space-y-4 shadow">
        @csrf

        <!-- Email -->
        <div>
            <label for="email" class="block mb-1 text-sm text-slate-300">Email</label>
            <input id="email" name="email" type="email" required autofocus
                value="{{ old('email') }}"
                class="w-full p-2 rounded text-black" />
            @error('email')
                <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block mb-1 text-sm text-slate-300">Password</label>
            <input id="password" name="password" type="password" required
                class="w-full p-2 rounded text-black" />
            @error('password')
                <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="flex items-center gap-2 text-sm text-slate-300">
            <input id="remember_me" type="checkbox" name="remember" class="rounded">
            <label for="remember_me">Remember me</label>
        </div>

        <!-- Submit & Forgot -->
        <div class="flex items-center justify-between mt-4 text-sm">
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-cyan-400 hover:underline">
                    Forgot your password?
                </a>
            @endif

            <button type="submit"
                class="bg-cyan-500 hover:bg-cyan-600 text-white font-semibold py-2 px-4 rounded transition">
                ✅ Log in
            </button>
        </div>
    </form>

    <p class="text-center text-sm text-slate-400 mt-4">
        Don’t have an account?
        <a href="{{ route('register') }}" class="text-cyan-400 hover:underline">Sign up here</a>
    </p>
</section>
@endsection