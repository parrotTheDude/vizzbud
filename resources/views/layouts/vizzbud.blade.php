<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Vizzbud | Scuba Dive Sites & Conditions')</title>

    {{-- Canonical: let your web server enforce www/non-www and HTTPS --}}
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Meta description --}}
    <meta name="description" content="@yield('meta_description', 'View personal dive logs, track stats, and explore dive sites on Vizzbud.')">

    {{-- Robots + Theme --}}
    <meta name="robots" content="index,follow">
    <meta name="theme-color" content="#0f172a">

    {{-- Favicons / PWA  --}}
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="theme-color" content="#ffffff">

    {{-- Social cards --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('og_title', 'Vizzbud')">
    <meta property="og:description" content="@yield('og_description', 'Dive sites, live conditions, and logs.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', asset('og-image.jpg'))">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('twitter_title', 'Vizzbud')">
    <meta name="twitter:description" content="@yield('twitter_description', 'Dive sites, live conditions, and logs.')">
    <meta name="twitter:image" content="@yield('twitter_image', asset('og-image.jpg'))">

    {{-- Fonts: preconnect + stylesheet preload --}}
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    {{-- Vite assets (CSS first, JS modules will be deferred) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Third-party JS: defer to avoid blocking --}}
    {{-- Alpine (CDN). Prefer npm package if possible. --}}
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Chart.js (only include on pages that chart; otherwise push via @stack) --}}
    @stack('charts-head')
    {{-- Example fallback: --}}
    {{-- <script defer src="https://cdn.jsdelivr.net/npm/chart.js"></script> --}}

    {{-- Mapbox CSS always safe, JS only when needed --}}
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css" rel="stylesheet">
    @stack('map') {{-- push the JS only on pages that need maps --}}

    {{-- Simple Analytics --}}
    <script async data-collect-dnt="true" src="https://scripts.simpleanalyticscdn.com/latest.js"></script>
    <noscript><img src="https://queue.simpleanalyticscdn.com/noscript.gif?collect-dnt=true" alt="" referrerpolicy="no-referrer-when-downgrade"></noscript>

    @stack('head')
</head>

<body class="bg-slate-900 text-white font-sans"
      x-data="{ open:false, scrolled:false }"
      x-init="
        const onScroll = () => scrolled = window.scrollY > 8;
        onScroll(); window.addEventListener('scroll', onScroll);
      "
      x-cloak>
  <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:bg-cyan-500 focus:text-slate-900 focus:rounded px-3 py-2">
    Skip to content
  </a>

  <header class="fixed inset-x-0 top-0 z-50">
    <div class="relative isolate">
      <!-- Glass panel background (sits behind the content) -->
      <div
        class="absolute inset-0 -z-10 transition-all duration-300"
        :class="scrolled
          ? 'bg-slate-900/70 backdrop-blur-xl backdrop-saturate-150 ring-1 ring-white/10 shadow-lg'
          : 'bg-slate-900/40 backdrop-blur-md backdrop-saturate-150 ring-1 ring-white/5'
        "
        style="will-change: backdrop-filter, background-color"
      ></div>

      <!-- Optional soft glow accent (can remove if you prefer cleaner look) -->
      <div class="pointer-events-none absolute -z-10 inset-x-8 -top-8 h-16 rounded-full bg-cyan-500/15 blur-2xl"></div>

      <!-- Content (your original container) -->
      <div class="mx-auto max-w-7xl px-6 h-16 flex items-center justify-between">
        <!-- Logo -->
        <a href="{{ route('home') }}" class="flex items-center gap-2 group">
          <img
            src="{{ asset('vizzbudLogo.png') }}"
            alt="Vizzbud"
            class="w-8 h-8 transition-transform group-hover:scale-105"
            width="32" height="32" loading="eager" decoding="async"
          >
          <span class="text-2xl font-bold tracking-tight text-white group-hover:text-cyan-400 transition">
            Vizzbud
          </span>
        </a>

        <!-- Desktop Nav (unchanged except for active state color) -->
        <nav class="hidden sm:flex items-center gap-6 text-sm font-medium">
          @php
            $links = [
              ['label' => 'Dive Sites', 'route' => 'dive-sites.index'],
              ['label' => 'Dive Log', 'route' => 'logbook.index'],
              ['label' => 'How it Works', 'route' => 'how_it_works'],
            ];
          @endphp

          @php
            $navLink = function ($label, $route, $activePatterns = []) {
                $isActive = request()->routeIs($activePatterns ?: $route);
                return sprintf(
                    '<a href="%s" class="relative transition-colors duration-200 %s
                        after:absolute after:-bottom-1 after:left-0 after:h-0.5 after:bg-cyan-400 after:transition-all after:duration-300 %s">%s</a>',
                    route($route),
                    $isActive ? 'text-cyan-300 after:w-full' : 'text-white hover:text-cyan-300 after:w-0 hover:after:w-full',
                    '',
                    e($label)
                );
            };
          @endphp

          @foreach ($links as $link)
            {!! $navLink($link['label'], $link['route']) !!}
          @endforeach

          @auth
            @if (auth()->user()->isAdmin())
              {!! $navLink('Blog', 'blog.index', ['blog.*']) !!}
              {!! $navLink('Admin', 'admin.dashboard') !!}
              {!! $navLink('Manage Blog', 'admin.blog.index', ['admin.blog.*']) !!}
            @endif

            <form method="POST" action="{{ route('logout') }}" class="relative">
              @csrf
              <button type="submit"
                class="relative transition-colors duration-200 text-white hover:text-cyan-300
                      after:absolute after:-bottom-1 after:left-0 after:h-0.5 after:w-0
                      after:bg-cyan-400 after:transition-all after:duration-300 hover:after:w-full">
                Logout
              </button>
            </form>
          @else
            {!! $navLink('Login', 'login') !!}
          @endauth
        </nav>

        <!-- Hamburger -->
        <button
          @click="open = !open"
          @keydown.escape.window="open = false"
          :aria-expanded="open.toString()"
          aria-controls="mobile-menu"
          aria-label="Toggle navigation"
          class="sm:hidden text-gray-300 hover:text-white focus:outline-none transition"
        >
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 6h16M4 12h16M4 18h16" />
            <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
  </header>

  <!-- Mobile Drawer -->
  <div
    id="mobile-menu"
    x-show="open"
    x-transition.opacity.duration.150ms
    class="fixed inset-0 z-50 sm:hidden"
    @click.self="open = false"
    @keydown.escape.window="open = false"
    style="display:none;"
    aria-modal="true" role="dialog"
  >

    <!-- Backdrop -->
    <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm"></div>

    <!-- Sliding Panel -->
    <nav
      class="absolute right-0 top-0 z-20 h-full w-[85%] max-w-sm
            bg-white/10 backdrop-blur-2xl border-l border-white/10
            ring-1 ring-white/10 shadow-2xl rounded-l-2xl overflow-y-auto
            transition-transform duration-300 ease-out will-change-transform"
      :class="open ? 'translate-x-0' : 'translate-x-full'"
      x-init="$watch('open', v => v && $nextTick(() => $refs.firstLink?.focus()))"
    >

      <!-- Header -->
      <div class="relative flex items-center gap-3 px-6 pt-[env(safe-area-inset-top)] h-16
                  bg-gradient-to-r from-cyan-500/15 to-teal-400/10">
        <img src="{{ asset('vizzbudLogo.png') }}" alt="" class="w-7 h-7 rounded-md" />
        <div class="flex-1">
          <div class="text-white font-semibold leading-tight">Vizzbud</div>
          <div class="text-xs text-white/60">Dive smarter</div>
        </div>
        <button
          type="button"
          @click="open = false"
          aria-label="Close menu"
          class="shrink-0 rounded-lg p-2 text-white/70 hover:text-white hover:bg-white/10
                border border-white/10"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Body -->
      <div class="flex h-[calc(100%-4rem)] flex-col">
        <!-- Glow accent -->
        <div class="pointer-events-none h-8 mx-6 mt-3 rounded-full bg-white/20 blur-2xl"></div>

        <!-- Main links -->
        <ul class="px-4 pt-2 pb-3 space-y-1.5">
          @foreach ($links as $link)
            <li>
              <a
                href="{{ route($link['route']) }}"
                @click="open = false"
                x-ref="firstLink"
                class="group flex items-center gap-3 rounded-xl px-4 py-3
                      bg-white/5 hover:bg-white/10
                      border border-white/10
                      text-white transition"
              >
                <span class="flex-1">{{ $link['label'] }}</span>
                <svg class="h-4 w-4 text-white/50 group-hover:text-cyan-300 transition"
                    viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path fill-rule="evenodd"
                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707A1 1 0 118.707 5.293l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                        clip-rule="evenodd"/>
                </svg>
              </a>
            </li>
          @endforeach
        </ul>

        <!-- Divider -->
        <div class="mx-6 my-2 h-px bg-white/10"></div>

        <!-- Account / Admin -->
        <div class="px-4 pb-[max(env(safe-area-inset-bottom),1rem)] space-y-1.5">
          @auth
            @if (auth()->user()->isAdmin())
              <a href="{{ route('blog.index') }}" @click="open=false"
                class="group flex items-center gap-3 rounded-xl px-4 py-3
                        bg-white/5 hover:bg-white/10 border border-white/10 text-white transition">
                <span class="flex-1">Blog</span>
                <span class="text-white/40 group-hover:text-cyan-300">↗</span>
              </a>
              <a href="{{ route('admin.dashboard') }}" @click="open=false"
                class="group flex items-center gap-3 rounded-xl px-4 py-3
                        bg-white/5 hover:bg-white/10 border border-white/10 text-white transition">
                <span class="flex-1">Admin</span>
                <span class="text-white/40 group-hover:text-cyan-300">↗</span>
              </a>
              <a href="{{ route('admin.blog.index') }}" @click="open=false"
                class="group flex items-center gap-3 rounded-xl px-4 py-3
                        bg-white/5 hover:bg-white/10 border border-white/10 text-white transition">
                <span class="flex-1">Manage Blog</span>
                <span class="text-white/40 group-hover:text-cyan-300">↗</span>
              </a>
            @endif

            <form method="POST" action="{{ route('logout') }}" class="pt-2">
              @csrf
              <button type="submit"
                      @click="open = false"
                      class="w-full rounded-xl px-4 py-3 font-semibold
                            bg-gradient-to-r from-cyan-500/90 to-teal-400/90
                            hover:from-cyan-400/90 hover:to-teal-300/90
                            border border-white/10 ring-1 ring-white/10
                            text-white shadow-lg shadow-cyan-500/20 transition">
                Logout
              </button>
            </form>
          @else
            <a href="{{ route('login') }}" @click="open=false"
              class="w-full inline-flex items-center justify-center rounded-xl px-4 py-3 font-semibold
                      bg-white/10 hover:bg-white/20 border border-white/10 ring-1 ring-white/10
                      text-white transition">
              Login
            </a>
          @endauth
        </div>
      </div>
    </nav>
  </div>

  <!-- Main -->
  <main id="main" class="relative z-0 pt-16">
    @yield('content')
  </main>

  <!-- Footer -->
  <footer class="relative border-t border-slate-800 bg-slate-900/60 backdrop-blur-xl backdrop-saturate-150">
    <div class="mx-auto max-w-7xl px-6 py-8 flex flex-col items-center text-center text-slate-400 text-sm">
      
      <!-- Logo -->
      <img src="{{ asset('vizzbudLogo.png') }}" alt="Vizzbud Logo" class="w-10 h-10 mb-2">

      <!-- Brand -->
      <span class="text-white font-semibold text-lg">Vizzbud</span>
      <span class="mt-1 text-slate-400">Dive smarter. Made for divers worldwide.</span>

      <!-- Copyright -->
      <span class="mt-4 text-xs text-slate-500">© 2025 Vizzbud · Made for divers</span>
    </div>
  </footer>

  @stack('scripts')
</body>