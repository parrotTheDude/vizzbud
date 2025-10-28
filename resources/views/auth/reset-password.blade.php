@extends('layouts.vizzbud')

@section('title', 'Reset Your Password | Vizzbud')
@section('meta_description', 'Enter a new password to regain access to your Vizzbud account and continue logging your scuba dives.')

@push('head')
  {{-- Auth pages should never be indexed --}}
  <meta name="robots" content="noindex, nofollow">

  {{-- Canonical --}}
  <link rel="canonical" href="{{ url('/reset-password') }}">

  {{-- Open Graph / Twitter for private shares --}}
  <meta property="og:title" content="Reset Your Password | Vizzbud">
  <meta property="og:description" content="Securely set a new password to restore access to your Vizzbud dive log and account.">
  <meta property="og:image" content="{{ asset('images/divesites/default.webp') }}">
  <meta property="og:type" content="website">
  <meta property="og:url" content="{{ url('/reset-password') }}">
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
    <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Reset your password</h1>
    <p class="mt-2 text-white/70 text-sm">Choose a strong new password and you’re back in.</p>
  </header>

  {{-- form card --}}
  <form method="POST" action="{{ route('password.store') }}"
        x-data="passwordForm('{{ old('email', $request->email) }}', '{{ $request->route('token') }}')"
        class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-white/10 backdrop-blur-xl shadow-xl p-6 space-y-5"
        @submit.prevent="if (isValid) $el.submit()">
    @csrf

    {{-- hidden token & email --}}
    <input type="hidden" name="token" :value="token">
    <input type="hidden" name="email" x-model="email" />

    {{-- new password --}}
    <div>
      <label for="password" class="block mb-1 text-[0.8rem] tracking-wide text-white/80">New password</label>
      <input :type="showPw ? 'text' : 'password'" id="password" name="password" x-model="password"
             class="w-full rounded-xl bg-white/10 border border-white/10 ring-1 ring-white/10
                    px-4 py-2.5 pr-24 text-white placeholder-white/40 outline-none
                    focus:border-cyan-400/40 focus:ring-cyan-400/30"
             placeholder="••••••••" required autocomplete="new-password" />
    </div>

    {{-- confirm password --}}
    <div>
      <label for="password_confirmation" class="block mb-1 text-[0.8rem] tracking-wide text-white/80">Confirm password</label>
      <input :type="showConfirm ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" x-model="confirm"
             class="w-full rounded-xl bg-white/10 border border-white/10 ring-1 ring-white/10
                    px-4 py-2.5 pr-24 text-white placeholder-white/40 outline-none
                    focus:border-cyan-400/40 focus:ring-cyan-400/30"
             placeholder="••••••••" required autocomplete="new-password" />
    </div>

    {{-- strength meter --}}
    <div class="mt-4 space-y-2" aria-live="polite">
      <div class="flex gap-1.5">
        <template x-for="i in 4" :key="i">
          <div class="h-2 flex-1 rounded-full border border-white/10 ring-1 ring-white/10 transition-all duration-300"
               :class="segmentClass(i)"></div>
        </template>
      </div>
      <div class="flex items-center justify-between text-xs">
        <span class="text-white/60">Password strength</span>
        <span :class="strength.textClass" x-text="strength.label"></span>
      </div>
    </div>

    {{-- checklist --}}
    <ul class="mt-2 space-y-1.5 text-sm">
      <template x-for="rule in rules" :key="rule.text">
        <li class="flex items-center gap-2">
          <span class="inline-block h-2.5 w-2.5 rounded-full"
                :class="rule.valid ? 'bg-emerald-400' : 'bg-white/30'"></span>
          <span :class="rule.valid ? 'text-emerald-300' : 'text-white/70'" x-text="rule.text"></span>
        </li>
      </template>
    </ul>

    {{-- submit --}}
    <button type="submit"
            :disabled="!isValid"
            class="group inline-flex w-full items-center justify-center gap-2
                   rounded-xl px-4 py-2.5 font-semibold text-white
                   bg-gradient-to-r from-cyan-500/90 to-teal-400/90
                   hover:from-cyan-400/90 hover:to-teal-300/90
                   border border-white/10 ring-1 ring-white/10
                   backdrop-blur-md shadow-lg shadow-cyan-500/20
                   transition-all duration-300 hover:-translate-y-0.5
                   disabled:opacity-50 disabled:cursor-not-allowed">
      <span>Reset password</span>
    </button>
  </form>
</section>

<script>
function passwordForm(email, token) {
  return {
    email, token,
    password: '',
    confirm: '',
    showPw: false,
    showConfirm: false,

    get rules() {
      return [
        { text: 'Minimum 8 characters',   valid: this.password.length >= 8 },
        { text: 'One lowercase letter',   valid: /[a-z]/.test(this.password) },
        { text: 'One uppercase letter',   valid: /[A-Z]/.test(this.password) },
        { text: 'One number',             valid: /[0-9]/.test(this.password) },
        { text: 'One special character',  valid: /[@$!%*#?&\\-_.]/.test(this.password) },
        { text: 'Passwords match',        valid: this.confirm === this.password && this.password !== '' },
      ];
    },

    // strength score based on first 5 rules
    get strengthScore() {
      return this.rules.slice(0, 5).filter(r => r.valid).length; // 0..5
    },

    // map 0–5 score → 4 levels
    get strength() {
      const s = this.strengthScore;
      if (s <= 1) return { label: 'Weak', textClass: 'text-rose-300' };
      if (s === 2) return { label: 'Average', textClass: 'text-amber-300' };
      if (s === 3 || s === 4) return { label: 'Strong', textClass: 'text-lime-300' };
      if (s === 5) return { label: 'Very Strong', textClass: 'text-emerald-300' };
      return { label: 'Poor', textClass: 'text-rose-300' };
    },

    // segment coloring (4 bars)
    segmentClass(i) {
      const s = this.strengthScore;
      const colors = ['bg-rose-400','bg-amber-400','bg-lime-400','bg-emerald-500'];
      return i <= Math.min(4, Math.ceil(s / 1.25)) ? colors[i-1] : 'bg-white/10';
    },

    get isValid() {
      return this.rules.every(r => r.valid);
    }
  }
}
</script>
@endsection