@extends('layouts.vizzbud')

@section('title', 'Access Denied | Vizzbud')
@section('meta_description', 'You don’t have permission to access this page on Vizzbud.')

@push('head')
  {{-- 🚫 Prevent search engines from indexing restricted content --}}
  <meta name="robots" content="noindex, nofollow">

  {{-- Canonical (for clarity, though shouldn’t be crawled) --}}
  <link rel="canonical" href="{{ url()->current() }}">

  {{-- Theme and identity --}}
  <meta name="theme-color" content="#0f172a">
  <meta name="application-name" content="Vizzbud">
  <meta name="color-scheme" content="dark light">

  {{-- Optional structured data --}}
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "WebPage",
    "name": "Access Denied",
    "description": "This page is restricted and requires additional permissions on Vizzbud.",
    "isPartOf": {
      "@type": "WebSite",
      "name": "Vizzbud",
      "url": "https://vizzbud.com"
    }
  }
  </script>
@endpush

@section('content')
<section class="relative flex items-center justify-center px-6 py-24 sm:py-32">
  {{-- background glow --}}
  <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(34,211,238,0.08),transparent_70%)]"></div>

  <div class="relative max-w-xl w-full text-center rounded-2xl
              bg-white/10 backdrop-blur-xl border border-white/20 ring-1 ring-white/10 shadow-2xl
              px-8 py-12">

    {{-- 403 number --}}
    <h1 class="text-6xl sm:text-7xl font-extrabold tracking-tight text-white mb-4">403</h1>

    {{-- message --}}
    <p class="text-slate-300 text-lg mb-8">
      You don’t have permission to access this page.<br>
      Maybe try logging in with a different account?
    </p>

    {{-- actions --}}
    <div class="flex flex-col sm:flex-row gap-3 justify-center">
      <a href="{{ route('login') }}"
         class="flex items-center justify-center gap-2 rounded-lg px-5 py-3
                bg-cyan-600/90 hover:bg-cyan-500 text-white font-semibold shadow-md
                transition transform hover:scale-[1.02]">
        🔑 Log In
      </a>
      <a href="{{ route('home') }}"
         class="flex items-center justify-center gap-2 rounded-lg px-5 py-3
                bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/20
                text-slate-200 font-semibold shadow-sm transition">
        🏝️ Back to Home
      </a>
    </div>
  </div>
</section>
@endsection