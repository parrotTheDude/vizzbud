<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Basic Meta -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Title & Description -->
  <title>@yield('title', 'Vizzbud | Scuba Dive Sites & Conditions')</title>
  <meta name="description" content="@yield('meta_description', 'Track dives, view conditions, and explore global dive sites on Vizzbud.')">

  <!-- Canonical -->
  <link rel="canonical" href="https://vizzbud.com{{ request()->getPathInfo() }}">

  <!-- Robots -->
  @hasSection('noindex')
    <meta name="robots" content="noindex,follow">
  @else
    <meta name="robots" content="index,follow">
  @endif

  <!-- Theme -->
  <meta name="color-scheme" content="dark light">

  <!-- Open Graph / Twitter -->
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Vizzbud">
  <meta property="og:title" content="@yield('og_title', 'Vizzbud')">
  <meta property="og:description" content="@yield('og_description', 'Dive sites, live conditions, and dive logs.')">
  <meta property="og:url" content="https://vizzbud.com{{ request()->getPathInfo() }}">
  <meta property="og:image" content="@yield('og_image', asset('og-image.webp'))">
  <meta property="og:locale" content="en_AU">
  <meta property="og:image:alt" content="Vizzbud – Dive smarter with live conditions and dive site guides">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="@yield('twitter_title', 'Vizzbud')">
  <meta name="twitter:description" content="@yield('twitter_description', 'Track dives, see live conditions, and plan your next dive.')">
  <meta name="twitter:image" content="@yield('twitter_image', asset('og-image.webp'))">
  <meta name="keywords" content="@yield('keywords', 'scuba diving, dive sites, dive conditions, diving Australia, Vizzbud')">

  <!-- Favicons -->
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
  <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
  <link rel="manifest" href="{{ asset('manifest.json') }}">
  <meta name="theme-color" content="#06b6d4">
  <meta name="msapplication-TileColor" content="#0f172a">
  <link rel="alternate" href="https://vizzbud.com{{ request()->getPathInfo() }}" hreflang="en">

  <!-- Preconnects -->
  <link rel="preconnect" href="https://api.mapbox.com" crossorigin>
  <link rel="preconnect" href="https://unpkg.com" crossorigin>
  <link rel="preconnect" href="https://scripts.simpleanalyticscdn.com" crossorigin>
  <link rel="preconnect" href="https://queue.simpleanalyticscdn.com" crossorigin>

  <!-- Preloads -->
  <link rel="preload" as="image" href="{{ asset('vizzbudLogo.webp') }}" fetchpriority="high" imagesrcset="{{ asset('vizzbudLogo.webp') }}" imagesizes="32px">

  <!-- Scripts -->
  <link rel="preload" as="script" href="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" importance="low">

  <!-- Vite Assets -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <!-- Alpine.js -->
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <!-- Chart.js (when pushed) -->
  @stack('charts-head')

  <!-- Mapbox CSS -->
  <link href="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css" rel="stylesheet">
  @stack('map')

  <!-- Simple Analytics -->
  <script async data-collect-dnt="true"
        src="https://scripts.simpleanalyticscdn.com/latest.js"
        onerror="console.debug('SimpleAnalytics blocked or offline')"></script>
  <noscript><img src="https://queue.simpleanalyticscdn.com/noscript.gif?collect-dnt=true" alt=""></noscript>

  <!-- JSON-LD Structured Data -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "Vizzbud",
    "url": "https://vizzbud.com",
    "logo": "https://vizzbud.com/android-chrome-512x512.png",
    "sameAs": [
      "https://www.instagram.com/vizzbud"
    ]
  }
  </script>

  <!-- iOS Safe Area Support -->
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Vizzbud">

  <!-- Core Web Vitals -->
  <meta http-equiv="Accept-CH" content="DPR, Viewport-Width, Width">

  <script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js')
      .then(() => console.log('Service Worker registered'))
      .catch((err) => console.warn('Service Worker failed:', err));
  }
  </script>

  <!-- Page-specific additions -->
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
      <!-- Glass panel background -->
      <div
        class="absolute inset-0 -z-10 transition-all duration-300"
        :class="scrolled
          ? 'bg-slate-900/70 backdrop-blur-xl backdrop-saturate-150 ring-1 ring-white/10 shadow-lg'
          : 'bg-slate-900/40 backdrop-blur-md backdrop-saturate-150 ring-1 ring-white/5'
        "
        style="will-change: backdrop-filter, background-color"
      ></div>

      <div class="pointer-events-none absolute -z-10 inset-x-8 -top-8 h-16 rounded-full bg-cyan-500/15 blur-2xl"></div>

      <!-- Content-->
      <div class="mx-auto max-w-7xl px-6 h-16 flex items-center justify-between">
        <!-- Logo -->
        <a href="{{ route('home') }}" class="flex items-center gap-2 group">
          <img
            src="{{ asset('vizzbudLogo.webp') }}"
            alt="Vizzbud Logo"
            class="w-8 h-8 transition-transform group-hover:scale-105"
            width="32" height="32" loading="eager" decoding="async"
          >
          <span class="text-2xl font-bold tracking-tight text-white group-hover:text-cyan-400 transition">
            Vizzbud
          </span>
        </a>

        <!-- Desktop Nav -->
        <nav class="hidden sm:flex items-center gap-6 text-sm font-medium">
          @php
            $links = [
              ['label' => 'Dive Sites', 'route' => 'dive-map.index'],
              ['label' => 'Dive Log', 'route' => 'logbook.index'],
            ];
          @endphp

          @php
            // Only show “How it Works” if the user is not logged in
            if (!auth()->check()) {
                $links[] = ['label' => 'How it Works', 'route' => 'how_it_works'];
            }
          @endphp

          @php
            $navLink = function ($label, $route, $activePatterns = []) {
                return sprintf(
                    '<a href="%s" class="relative transition-colors duration-200 %s
                        after:absolute after:-bottom-1 after:left-0 after:h-0.5 after:bg-cyan-400 after:transition-all after:duration-300 %s">%s</a>',
                    route($route),
                    'text-white hover:text-cyan-300 after:w-0 hover:after:w-full',
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
              {!! $navLink('Admin', 'admin.dashboard') !!}
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
  class="fixed inset-0 z-50 sm:hidden"
  @click.self="open = false"
  @keydown.escape.window="open = false"
  x-transition.opacity.duration.200ms
  style="display:none;"
>
  <!-- Backdrop -->
  <div class="absolute inset-0 z-10 bg-slate-900/70 backdrop-blur-sm"></div>

  <!-- Drawer -->
  <nav
    class="absolute right-0 top-0 z-20 h-full w-[86%] max-w-sm
           bg-white/10 backdrop-blur-2xl border-l border-white/10
           ring-1 ring-white/10 shadow-2xl rounded-l-2xl overflow-y-auto
           will-change-transform"
    :class="open ? 'translate-x-0' : 'translate-x-full'"
    x-init="$watch('open', v => v && $nextTick(() => $refs.firstLink?.focus()))"
    x-transition:enter="transform transition ease-out duration-300"
    x-transition:enter-start="translate-x-full opacity-0"
    x-transition:enter-end="translate-x-0 opacity-100"
    x-transition:leave="transform transition ease-in duration-250"
    x-transition:leave-start="translate-x-0 opacity-100"
    x-transition:leave-end="translate-x-full opacity-0"
  >
    <!-- Drawer header -->
    <div class="relative flex items-center justify-between px-5 pt-5 pb-3">
      <div class="flex items-center gap-2">
        <img src="{{ asset('vizzbudLogo.webp') }}" alt="Vizzbud" class="w-7 h-7 rounded-md">
        <span class="text-lg font-semibold">Vizzbud</span>
      </div>
      <button
        type="button"
        @click="open = false"
        aria-label="Close menu"
        class="inline-flex items-center justify-center rounded-xl p-2
               text-slate-300 hover:text-white hover:bg-white/10
               focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400"
      >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Accent strip -->
    <div class="mx-5 mb-3 h-px bg-gradient-to-r from-cyan-400/50 via-teal-300/40 to-transparent"></div>

    <!-- Menu -->
    <div class="px-4 pb-8">
      <ul class="space-y-1.5">
        @foreach ($links as $link)
          <li>
            <a
              href="{{ route($link['route']) }}"
              @click="open = false"
              x-ref="firstLink"
              class="flex items-center gap-3 w-full rounded-xl px-3.5 py-3
                     text-[15px] text-white/90
                     hover:bg-white/10 hover:text-white
                     active:scale-[.99] transition
                     focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400"
            >
              <span class="inline-block h-2 w-2 rounded-full bg-cyan-400/80"></span>
              <span class="flex-1">{{ $link['label'] }}</span>
              <svg class="h-4 w-4 text-white/60 group-hover:text-white/80 transition"
                   fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5l7 7-7 7" />
              </svg>
            </a>
          </li>
        @endforeach

        @auth
          @if (auth()->user()->isAdmin())
            <li>
              <a href="{{ route('admin.dashboard') }}" @click="open=false"
                 class="flex items-center gap-3 w-full rounded-xl px-3.5 py-3
                        text-[15px] text-white/90 hover:bg-white/10 hover:text-white
                        active:scale-[.99] transition focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400">
                <span class="inline-block h-2 w-2 rounded-full bg-cyan-400/80"></span>
                <span class="flex-1">Admin</span>
                <svg class="h-4 w-4 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5l7 7-7 7" />
                </svg>
              </a>
            </li>
          @endif

          <li class="mt-4 pt-4 border-t border-white/10">
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit"
                @click="open = false"
                class="flex items-center gap-3 w-full rounded-xl px-3.5 py-3
                       text-[15px] text-white/90 hover:bg-white/10 hover:text-white
                       active:scale-[.99] transition focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400">
                <span class="inline-block h-2 w-2 rounded-full bg-cyan-400/80"></span>
                <span class="flex-1 text-left">Logout</span>
              </button>
            </form>
          </li>
        @else
          <li class="mt-4 pt-4 border-t border-white/10">
            <a href="{{ route('login') }}" @click="open = false"
               class="flex items-center gap-3 w-full rounded-xl px-3.5 py-3
                      text-[15px] text-white/90 hover:bg-white/10 hover:text-white
                      active:scale-[.99] transition focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400">
              <span class="inline-block h-2 w-2 rounded-full bg-cyan-400/80"></span>
              <span class="flex-1">Login</span>
              <svg class="h-4 w-4 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5l7 7-7 7" />
              </svg>
            </a>
          </li>
        @endauth
      </ul>
    </div>
  </nav>
</div>

  <!-- Main -->
  <main id="main" class="relative z-0 pt-16">
    @yield('content')
  </main>

  <footer class="relative border-t border-slate-800 bg-slate-900/80 backdrop-blur-xl backdrop-saturate-150 z-10">
    <div class="mx-auto max-w-7xl px-6 py-10 text-center text-slate-300 text-sm space-y-6">

      <!-- Logo + Brand -->
      <div class="flex flex-col items-center">
        <img src="{{ asset('vizzbudLogo.webp') }}" alt="Vizzbud Logo" class="w-10 h-10 mb-2">
        <span class="text-white font-semibold text-lg">Vizzbud</span>
        <span class="text-slate-400 text-sm">Dive smarter. Made for divers worldwide.</span>
      </div>

      <!-- Navigation Links -->
      <nav class="flex flex-wrap justify-center gap-4 sm:gap-6 text-[13px] text-slate-400">
        <a href="{{ route('privacy') }}" class="hover:text-cyan-400 transition">Privacy Policy</a>
        <a href="{{ url('/dive-sites') }}" class="hover:text-cyan-400 transition">Dive Sites</a>
        <a href="{{ route('terms') }}" class="hover:text-cyan-400 transition">Terms of Service</a>
      </nav>

      <!-- Social Icons -->
      <div class="flex justify-center gap-5 text-slate-400">
        <a href="https://instagram.com/vizzbud.dive" target="_blank" rel="noopener" class="hover:text-cyan-400 transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M7 2C4.24 2 2 4.24 2 7v10c0 2.76 2.24 5 5 5h10c2.76 0 5-2.24 5-5V7c0-2.76-2.24-5-5-5H7zm10 2c1.65 0 3 1.35 3 3v10c0 1.65-1.35 3-3 3H7c-1.65 0-3-1.35-3-3V7c0-1.65 1.35-3 3-3h10zM12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10zm0 2a3 3 0 1 1 0 6 3 3 0 0 1 0-6zm4.5-3a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3z"/>
          </svg>
        </a>
      </div>

      <!-- Disclaimer -->
      <p class="max-w-xl mx-auto text-[13px] text-slate-400 leading-relaxed">
        <strong class="text-slate-200">Disclaimer:</strong> Dive conditions shown on Vizzbud are based on external data sources 
        and forecasts. Always use your own experience, training, and local knowledge to assess dive safety before entering the water.
      </p>

      <!-- Copyright -->
      <span class="text-[12px] text-slate-500">© 2025 Vizzbud · Made for divers</span>
    </div>
  </footer>

  @stack('scripts')
</body>