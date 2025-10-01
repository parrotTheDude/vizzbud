@extends('layouts.vizzbud')

@section('title', 'Page Not Found | Vizzbud')
@section('meta_description', 'Oops! The page you’re looking for doesn’t exist. Return to the map or explore dive sites on Vizzbud.')

@section('content')
<section class="relative flex items-center justify-center px-6 py-24 sm:py-32">
  {{-- background glow --}}
  <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(34,211,238,0.08),transparent_70%)]"></div>

  <div class="relative max-w-xl w-full text-center rounded-2xl
              bg-white/10 backdrop-blur-xl border border-white/20 ring-1 ring-white/10 shadow-2xl
              px-8 py-12">
    
    {{-- 404 number --}}
    <h1 class="text-6xl sm:text-7xl font-extrabold tracking-tight text-white mb-4">404</h1>

    {{-- message --}}
    <p class="text-slate-300 text-lg mb-8">
      Oops! We couldn’t find that page.
    </p>

    {{-- actions --}}
    <div class="flex flex-col sm:flex-row gap-3 justify-center">
      <a href="{{ route('home') }}"
         class="flex items-center justify-center gap-2 rounded-lg px-5 py-3
                bg-cyan-600/90 hover:bg-cyan-500 text-white font-semibold shadow-md
                transition transform hover:scale-[1.02]">
        ⛵ Back to Home
      </a>
      <a href="{{ route('dive-sites.index') }}"
         class="flex items-center justify-center gap-2 rounded-lg px-5 py-3
                bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/20
                text-slate-200 font-semibold shadow-sm transition">
        🌊 Explore Dive Sites
      </a>
    </div>
  </div>
</section>
@endsection