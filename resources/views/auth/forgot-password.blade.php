@extends('layouts.vizzbud')

@section('title', 'Forgot Your Password? | Vizzbud')
@section('meta_description', 'Request a password reset link for your Vizzbud account. Regain access to your dive log and site data in just a few clicks.')

@push('head')
  {{-- Auth pages should never be indexed --}}
  <meta name="robots" content="noindex, nofollow">

  {{-- Canonical --}}
  <link rel="canonical" href="{{ url('/forgot-password') }}">

  {{-- Optional Open Graph (for private shares) --}}
  <meta property="og:title" content="Forgot Your Password? | Vizzbud">
  <meta property="og:description" content="Request a secure password reset link to regain access to your Vizzbud account and dive log.">
  <meta property="og:image" content="{{ asset('images/divesites/default.webp') }}">
  <meta property="og:type" content="website">
  <meta property="og:url" content="{{ url('/forgot-password') }}">
  <meta name="twitter:card" content="summary">
@endpush

@section('content')
<section class="relative max-w-md mx-auto px-6 py-16">

  {{-- ambient glow --}}
  <div class="pointer-events-none absolute inset-0 -z-10">
    <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-[26rem] h-[26rem] rounded-full
                bg-cyan-500/10 blur-3xl"></div>
  </div>

  {{-- header --}}
  <header class="mb-8 text-center">
    <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Reset your password</h1>
    <p class="mt-2 text-white/70 text-sm">We’ll send a link to help you get back in.</p>
  </header>

  {{-- status alert --}}
  @if (session('status'))
    <div class="mb-4 rounded-xl px-4 py-3 text-sm font-medium
                bg-emerald-500/15 text-emerald-200 border border-white/10 ring-1 ring-emerald-400/30">
      {{ session('status') }}
    </div>
  @endif

  {{-- form card --}}
  <form method="POST" action="{{ route('password.email') }}"
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
      </div>
      @error('email')
        <div class="mt-1 text-sm text-rose-300">{{ $message }}</div>
      @enderror
    </div>

    {{-- full width button --}}
    <button type="submit"
            class="group inline-flex items-center justify-center gap-2 w-full
                   rounded-xl px-4 py-3 font-semibold text-white
                   bg-gradient-to-r from-cyan-500/90 to-teal-400/90
                   hover:from-cyan-400/90 hover:to-teal-300/90
                   border border-white/10 ring-1 ring-white/10
                   backdrop-blur-md shadow-lg shadow-cyan-500/20
                   transition-all duration-300 hover:-translate-y-0.5">
      <span>Send reset link</span>
    </button>

    <x-vizzbud.captcha />
  </form>

  {{-- back + signup --}}
  <div class="mt-6 space-y-3 text-center text-sm">
    <a href="{{ route('login') }}" class="block text-cyan-300 hover:text-cyan-200">← Back to login</a>
    <p class="text-white/70">
      Don’t have an account?
      <a href="{{ route('register') }}" class="text-cyan-300 hover:text-cyan-200 font-medium">Sign up</a>
    </p>
  </div>
</section>
@endsection