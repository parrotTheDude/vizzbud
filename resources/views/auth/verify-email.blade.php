@extends('layouts.vizzbud')

@section('title', 'Verify Your Email | Vizzbud')
@section('meta_description', 'Please verify your email to activate your Vizzbud account and start logging your scuba dives and viewing live dive site data.')

@push('head')
  {{-- Auth verification pages should never be indexed --}}
  <meta name="robots" content="noindex, nofollow">

  {{-- Canonical --}}
  <link rel="canonical" href="{{ url('/verify-email') }}">

  {{-- OG / Twitter (for private links in email clients etc.) --}}
  <meta property="og:title" content="Verify Your Email | Vizzbud">
  <meta property="og:description" content="Confirm your email address to complete registration and access your Vizzbud dive log and live site data.">
  <meta property="og:image" content="{{ asset('images/divesites/default.webp') }}">
  <meta property="og:type" content="website">
  <meta property="og:url" content="{{ url('/verify-email') }}">
  <meta name="twitter:card" content="summary">
@endpush

@section('content')
<section class="relative max-w-md mx-auto px-6 py-20 text-center">

  {{-- 💫 Ambient glow --}}
  <div class="pointer-events-none absolute inset-0 -z-10">
    <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[28rem] h-[28rem] rounded-full
                bg-gradient-to-tr from-cyan-500/20 to-teal-400/10 blur-3xl"></div>
  </div>

  {{-- ✅ Status message --}}
  @if (session('status') === 'verification-link-sent')
    <div x-data="{ show: true }"
         x-init="setTimeout(() => show = false, 5000)"
         x-show="show" x-transition
         class="mb-6 rounded-xl px-4 py-3 text-sm font-medium
                bg-emerald-500/15 text-emerald-200 border border-white/10 ring-1 ring-emerald-400/30">
      A new verification link has been sent to your email address.
    </div>
  @endif

  {{-- ⚠️ Cooldown notice --}}
  @if ($errors->has('cooldown'))
    <div x-data="{ show: true }"
         x-init="setTimeout(() => show = false, 5000)"
         x-show="show" x-transition
         class="mb-6 rounded-xl px-4 py-3 text-sm font-medium
                bg-amber-500/15 text-amber-200 border border-white/10 ring-1 ring-amber-400/30">
      {{ $errors->first('cooldown') }}
    </div>
  @endif

  {{-- ✉️ Main content --}}
  <div class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-gradient-to-br from-slate-900/70 via-slate-800/70 to-slate-900/70 backdrop-blur-xl shadow-xl p-10 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-r from-cyan-500/10 via-transparent to-teal-400/10 opacity-40 pointer-events-none"></div>

    <h1 class="text-3xl sm:text-4xl font-extrabold text-cyan-300 tracking-tight mb-4">
      Check your email to verify your account
    </h1>

    <p class="text-slate-300 text-sm leading-relaxed mb-8">
      We’ve sent a verification link to your inbox.  
      Check your <strong>email</strong> (and your <em>spam folder</em>) for a message from  
      <span class="text-cyan-400 font-medium">vizzbud.com</span>.
    </p>

    {{-- 🔁 Resend link --}}
    <form 
      method="POST" 
      action="{{ route('verification.send') }}" 
      x-data="{
        cooldown: {{ session('cooldown_seconds') ?? 0 }},
        startTimer() {
          if (this.cooldown > 0) {
            const i = setInterval(() => {
              if (this.cooldown > 0) this.cooldown--;
              else clearInterval(i);
            }, 1000);
          }
        }
      }"
      x-init="startTimer()"
    >
      @csrf
      <button type="submit"
              class="text-sm text-cyan-400 hover:text-cyan-300 font-semibold underline underline-offset-2 transition">
        Didn’t get an email? Click here
      </button>
      <template x-if="cooldown > 0">
        <p class="text-xs text-slate-500 mt-2">
          You can resend again in <span x-text="Math.ceil(cooldown)"></span>s
        </p>
      </template>
    </form>
  </div>

</section>
@endsection