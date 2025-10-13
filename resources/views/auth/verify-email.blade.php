@extends('layouts.vizzbud')

@section('title', 'Verify Your Email | Vizzbud')
@section('meta_description', 'Please verify your email to activate your Vizzbud account and start logging your scuba dives and viewing live dive site data.')

@section('content')
<section class="relative max-w-md mx-auto px-6 py-16">

  {{-- ambient glow --}}
  <div class="pointer-events-none absolute inset-0 -z-10">
    <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-[26rem] h-[26rem] rounded-full
                bg-cyan-500/10 blur-3xl"></div>
  </div>

  @if (session('status') === 'verification-link-sent')
    <div x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 5000)"
        x-show="show" x-transition
        class="mb-6 rounded-xl px-4 py-3 text-sm font-medium
                bg-emerald-500/15 text-emerald-200 border border-white/10 ring-1 ring-emerald-400/30">
      A new verification link has been sent to your email address.
    </div>
  @endif

  @if ($errors->has('cooldown'))
    <div x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 5000)"
        x-show="show" x-transition
        class="mb-6 rounded-xl px-4 py-3 text-sm font-medium
                bg-amber-500/15 text-amber-200 border border-white/10 ring-1 ring-amber-400/30">
      {{ $errors->first('cooldown') }}
    </div>
  @endif

  {{-- card --}}
  <div class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-white/10 backdrop-blur-xl shadow-xl p-6 text-center">
    <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Verify your email</h1>
    <p class="mt-2 text-white/70 text-sm">
      Thanks for signing up! To activate your account, please verify your email address.
      We’ve sent a verification link to your inbox. Didn’t get it?
    </p>

    {{-- resend --}}
    <form 
      method="POST" 
      action="{{ route('verification.send') }}" 
      x-data="{
        cooldown: {{ session('cooldown_seconds') ?? 0 }},
        startTimer() {
          if (this.cooldown > 0) {
            const interval = setInterval(() => {
              if (this.cooldown > 0) {
                this.cooldown--;
              } else {
                clearInterval(interval);
              }
            }, 1000);
          }
        }
      }"
      x-init="startTimer()"
      class="mt-6"
    >
      @csrf
      <button type="submit"
              x-bind:disabled="cooldown > 0"
              class="group inline-flex w-full items-center justify-center gap-2
                    rounded-xl px-4 py-3 font-semibold text-white
                    bg-gradient-to-r from-cyan-500/90 to-teal-400/90
                    hover:from-cyan-400/90 hover:to-teal-300/90
                    border border-white/10 ring-1 ring-white/10
                    backdrop-blur-md shadow-lg shadow-cyan-500/20
                    transition-all duration-300 hover:-translate-y-0.5
                    disabled:opacity-50 disabled:cursor-not-allowed">
        <template x-if="cooldown <= 0">
          <span>Resend verification email</span>
        </template>
        <template x-if="cooldown > 0">
          <span>Wait <span x-text="Math.ceil(cooldown)"></span>s</span>
        </template>

        <svg class="h-4 w-4 opacity-80 group-hover:translate-x-0.5 transition-transform" viewBox="0 0 24 24" fill="none">
          <path d="M5 12h14M13 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
    </form>

    {{-- divider --}}
    <div class="mt-6 flex items-center gap-3 text-xs text-white/50">
      <div class="h-px flex-1 bg-white/10"></div>
      <span>or</span>
      <div class="h-px flex-1 bg-white/10"></div>
    </div>

    {{-- logout (secondary action) --}}
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="mt-4">
      @csrf
      <button type="submit" class="text-cyan-300 hover:text-cyan-200 text-sm font-medium">
        Log out and verify later
      </button>
    </form>
  </div>

</section>
@endsection