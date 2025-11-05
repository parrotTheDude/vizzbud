@extends('layouts.vizzbud')

@section('title', 'Create Your Vizzbud Account')
@section('meta_description', 'Sign up for a free Vizzbud account to log your scuba dives, track stats, and explore real-time dive site conditions.')

@push('head')
  {{-- Auth and registration pages should never be indexed --}}
  <meta name="robots" content="noindex, nofollow">

  {{-- Canonical URL for clarity --}}
  <link rel="canonical" href="{{ url('/register') }}">

  {{-- Optional OG/Twitter for clean private shares --}}
  <meta property="og:title" content="Join Vizzbud | Create Your Account">
  <meta property="og:description" content="Create a Vizzbud account to log your dives, view stats, and explore live scuba dive conditions.">
  <meta property="og:image" content="{{ asset('images/divesites/default.webp') }}">
  <meta property="og:type" content="website">
  <meta property="og:url" content="{{ url('/register') }}">
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
    <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Create your account</h1>
    <p class="mt-2 text-white/70 text-sm">Start logging dives and tracking your stats.</p>
  </header>

  {{-- form card --}}
  <form method="POST" action="{{ route('register') }}"
        class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-white/10 backdrop-blur-xl shadow-xl p-6 space-y-5"
        x-data="passwordForm()">
    @csrf

    {{-- Name --}}
    <div>
      <label for="name" class="block mb-1 text-[0.8rem] tracking-wide text-white/80">Name</label>
      <input id="name" name="name" type="text" required autofocus
             value="{{ old('name') }}"
             class="w-full rounded-xl bg-white/10 border border-white/10 ring-1 ring-white/10
                    px-4 py-2.5 text-white placeholder-white/40 outline-none
                    focus:border-cyan-400/40 focus:ring-cyan-400/30"
             placeholder="Your name" />
      @error('name')
        <div class="mt-1 text-sm text-rose-300">{{ $message }}</div>
      @enderror
    </div>

    {{-- Email --}}
    <div>
      <label for="email" class="block mb-1 text-[0.8rem] tracking-wide text-white/80">Email</label>
      <input id="email" name="email" type="email" required
             value="{{ old('email') }}"
             class="w-full rounded-xl bg-white/10 border border-white/10 ring-1 ring-white/10
                    px-4 py-2.5 text-white placeholder-white/40 outline-none
                    focus:border-cyan-400/40 focus:ring-cyan-400/30"
             placeholder="you@example.com" />
      @error('email')
        <div class="mt-1 text-sm text-rose-300">{{ $message }}</div>
      @enderror
    </div>

    {{-- Passwords --}}
    <div class="space-y-4">
      {{-- Password --}}
      <div>
        <label for="password" class="block mb-1 text-[0.8rem] tracking-wide text-white/80">Password</label>
        <div class="relative">
          <input id="password" name="password" :type="showPassword ? 'text' : 'password'"
                 x-model="password" required autocomplete="new-password"
                 class="w-full rounded-xl bg-white/10 border border-white/10 ring-1 ring-white/10
                        px-4 py-2.5 pr-11 text-white placeholder-white/40 outline-none
                        focus:border-cyan-400/40 focus:ring-cyan-400/30"
                 placeholder="Create a password" />
          <button type="button" @click="showPassword = !showPassword"
                  class="absolute right-2 top-1/2 -translate-y-1/2 text-white/70 hover:text-white/90 text-xs px-2 py-1 rounded">
            <span x-text="showPassword ? 'Hide' : 'Show'"></span>
          </button>
        </div>
        @error('password')
          <div class="mt-1 text-sm text-rose-300">{{ $message }}</div>
        @enderror
      </div>

      {{-- Confirm --}}
      <div>
        <label for="password_confirmation" class="block mb-1 text-[0.8rem] tracking-wide text-white/80">Confirm password</label>
        <div class="relative">
          <input id="password_confirmation" name="password_confirmation" :type="showConfirm ? 'text' : 'password'"
                 x-model="confirm" required autocomplete="new-password"
                 class="w-full rounded-xl bg-white/10 border border-white/10 ring-1 ring-white/10
                        px-4 py-2.5 pr-14 text-white placeholder-white/40 outline-none
                        focus:border-cyan-400/40 focus:ring-cyan-400/30"
                 placeholder="Re-enter your password" />
          <button type="button" @click="showConfirm = !showConfirm"
                  class="absolute right-2 top-1/2 -translate-y-1/2 text-white/70 hover:text-white/90 text-xs px-2 py-1 rounded">
            <span x-text="showConfirm ? 'Hide' : 'Show'"></span>
          </button>
        </div>
      </div>

      {{-- strength meter (lightweight) --}}
      <div class="mt-1">
        <div class="h-1.5 w-full rounded-full bg-white/10 overflow-hidden">
          <div class="h-full transition-all"
               :class="[
                 score >= 4 ? 'bg-emerald-400' :
                 score >= 3 ? 'bg-cyan-400' :
                 score >= 2 ? 'bg-amber-400' : 'bg-rose-400'
               ]"
               :style="`width: ${(score/5)*100}%`"></div>
        </div>
        <div class="mt-1 text-[0.7rem] text-white/60">
          Password strength: <span x-text="label"></span>
        </div>
      </div>

      {{-- Rules checklist --}}
      <ul class="mt-2 grid grid-cols-1 gap-1 text-sm">
        <template x-for="rule in rules" :key="rule.text">
          <li class="flex items-center gap-2">
            <span class="inline-block h-2.5 w-2.5 rounded-full"
                  :class="rule.valid ? 'bg-emerald-400' : 'bg-white/30'"></span>
            <span :class="rule.valid ? 'text-emerald-300' : 'text-white/60'"
                  x-text="rule.text"></span>
          </li>
        </template>
      </ul>
    </div>

    {{-- Submit (auto spinner on valid submit) --}}
    <button type="submit"
            x-data="{ loading: false }"
            x-on:submit.window="
              // When the page is about to unload (form actually submitted), show spinner
              loading = true;
              setTimeout(() => loading = false, 6000); // safety reset if no reload
            "
            :disabled="loading"
            class="group inline-flex items-center justify-center gap-2 w-full
                  rounded-xl px-4 py-3 font-semibold text-white
                  bg-gradient-to-r from-cyan-500/90 to-teal-400/90
                  hover:from-cyan-400/90 hover:to-teal-300/90
                  border border-white/10 ring-1 ring-white/10
                  backdrop-blur-md shadow-lg shadow-cyan-500/20
                  transition-all duration-300 hover:-translate-y-0.5
                  disabled:opacity-60 disabled:cursor-not-allowed">

      {{-- Text --}}
      <span x-show="!loading" x-transition>Register</span>

      {{-- Spinner --}}
      <svg x-show="loading" x-transition
          class="animate-spin h-5 w-5 text-white"
          xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10"
                stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor"
              d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
      </svg>
    </button>

    <x-vizzbud.captcha />
  </form>

  {{-- secondary action --}}
  <p class="mt-6 text-center text-sm text-white/70">
    Already have an account?
    <a href="{{ route('login') }}" class="text-cyan-300 hover:text-cyan-200 font-medium">Log in</a>
  </p>
</section>

<script>
function passwordForm() {
  return {
    password: '',
    confirm: '',
    showPassword: false,
    showConfirm: false,

    get rules() {
      return [
        { text: 'Minimum 8 characters',  valid: this.password.length >= 8 },
        { text: 'One lowercase letter',  valid: /[a-z]/.test(this.password) },
        { text: 'One uppercase letter',  valid: /[A-Z]/.test(this.password) },
        { text: 'One number',            valid: /\d/.test(this.password) },
        { text: 'One special character', valid: /[!@#$%^&*(),.?":{}|<>_\-]/.test(this.password) },
        { text: 'Passwords match',       valid: this.confirm === this.password && this.confirm !== '' },
      ];
    },

    get score() {
      // count satisfied rules (exclude the match rule from strength)
      const base = this.rules.slice(0, 5).reduce((acc, r) => acc + (r.valid ? 1 : 0), 0);
      return base; // 0..5
    },

    get label() {
      const s = this.score;
      if (s >= 4) return 'Strong';
      if (s === 3) return 'Good';
      if (s === 2) return 'Weak';
      return 'Very weak';
    }
  };
}
</script>
@endsection