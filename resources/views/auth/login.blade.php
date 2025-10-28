@extends('layouts.vizzbud')

@section('title', 'Log In to Vizzbud')
@section('meta_description', 'Access your Vizzbud account to manage your dive logs, view stats, and explore live scuba dive conditions.')

@push('head')
  {{-- Auth pages should never be indexed --}}
  <meta name="robots" content="noindex, nofollow">

  {{-- Canonical (clean URL for consistency) --}}
  <link rel="canonical" href="{{ url('/login') }}">

  {{-- Optional Open Graph (so private shares look neat) --}}
  <meta property="og:title" content="Log In to Vizzbud">
  <meta property="og:description" content="Access your dive log, stats, and dive planning tools securely on Vizzbud.">
  <meta property="og:image" content="{{ asset('images/divesites/default.webp') }}">
  <meta property="og:type" content="website">
  <meta property="og:url" content="{{ url('/login') }}">
  <meta name="twitter:card" content="summary">
@endpush

@section('content')
<section class="relative max-w-md mx-auto px-6 py-16">

  {{-- ambient glow --}}
  <div class="pointer-events-none absolute inset-0 -z-10">
    <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-[28rem] h-[28rem] rounded-full
                bg-cyan-500/10 blur-3xl"></div>
  </div>

  {{-- header --}}
  <header class="mb-8 text-center">
    <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Log in to Vizzbud</h1>
    <p class="mt-2 text-white/70 text-sm">Welcome back — let’s get you diving.</p>
  </header>

  {{-- status alert --}}
  @if (session('status'))
    <div class="mb-4 rounded-xl px-4 py-3 text-sm font-medium
                bg-emerald-500/15 text-emerald-200 border border-white/10 ring-1 ring-emerald-400/30">
      {{ session('status') }}
    </div>
  @endif

  {{-- form card --}}
  <form method="POST" action="{{ route('login') }}"
        class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-white/10 backdrop-blur-xl shadow-xl p-6 space-y-5">
    @csrf

    {{-- email --}}
    <div>
      <label for="email" class="block mb-1 text-[0.8rem] tracking-wide text-white/80">Email</label>
      <div class="relative">
        <input id="email" name="email" type="email" required autofocus
               value="{{ old('email') }}"
               class="peer w-full rounded-xl bg-white/10 border border-white/10 ring-1 ring-white/10
                      px-4 py-2.5 text-white placeholder-white/40 outline-none
                      focus:border-cyan-400/40 focus:ring-cyan-400/30" placeholder="you@example.com" />
        <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-white/40">
        </div>
      </div>
      @error('email')
        <div class="mt-1 text-sm text-rose-300">{{ $message }}</div>
      @enderror
    </div>

    {{-- password --}}
    <div x-data="{ show: false }">
      <label for="password" class="block mb-1 text-[0.8rem] tracking-wide text-white/80">Password</label>
      <div class="relative">
        <input :type="show ? 'text' : 'password'" id="password" name="password" required
               class="peer w-full rounded-xl bg-white/10 border border-white/10 ring-1 ring-white/10
                      px-4 py-2.5 pr-11 text-white placeholder-white/40 outline-none
                      focus:border-cyan-400/40 focus:ring-cyan-400/30" placeholder="••••••••" />
        <button type="button" @click="show = !show"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-lg px-2 py-1 text-xs
                       text-white/70 hover:text-white/90 bg-white/5 border border-white/10">
          <span x-show="!show">Show</span>
          <span x-show="show">Hide</span>
        </button>
      </div>
      @error('password')
        <div class="mt-1 text-sm text-rose-300">{{ $message }}</div>
      @enderror
    </div>

    {{-- remember + forgot --}}
    <div class="flex items-center justify-between text-sm">
      <label for="remember_me" class="inline-flex items-center gap-2 select-none">
        <input id="remember_me" type="checkbox" name="remember"
               class="h-4 w-4 rounded border-white/20 bg-white/5 text-cyan-500
                      focus:ring-cyan-400/40 focus:ring-offset-0" />
        <span class="text-white/80">Remember me</span>
      </label>

      @if (Route::has('password.request'))
        <a href="{{ route('password.request') }}" class="text-cyan-300 hover:text-cyan-200">
          Forgot password?
        </a>
      @endif
    </div>

    {{-- submit --}}
    <button type="submit"
            class="group inline-flex w-full items-center justify-center gap-2
                   rounded-xl px-4 py-2.5 font-semibold text-white text-base
                   bg-gradient-to-r from-cyan-500/90 to-teal-400/90
                   hover:from-cyan-400/90 hover:to-teal-300/90
                   border border-white/10 ring-1 ring-white/10
                   backdrop-blur-md shadow-lg shadow-cyan-500/20
                   transition-all duration-300 hover:-translate-y-0.5">
      <span>Log in</span>
    </button>

    {{-- divider --}}
    <div class="flex items-center gap-3 text-xs text-white/50">
      <div class="h-px flex-1 bg-white/10"></div>
      <span>or</span>
      <div class="h-px flex-1 bg-white/10"></div>
    </div>

    {{-- link to register --}}
    <p class="text-center text-sm text-white/70">
      Don’t have an account?
      <a href="{{ route('register') }}" class="text-cyan-300 hover:text-cyan-200 font-medium">Sign up</a>
    </p>
  </form>
</section>
@endsection