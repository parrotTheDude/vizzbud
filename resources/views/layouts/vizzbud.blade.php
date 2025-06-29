<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Vizzbud | Scuba Dive Sites & Conditions')</title>
    <link rel="canonical" href="{{ preg_replace('/^www\./', '', url()->current()) }}">
    <meta name="description" content="@yield('meta_description', 'View your personal dive logs, track statistics, and explore new dive sites on Vizzbud.')">

    <!-- Mapbox -->
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css" rel="stylesheet">
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script defer src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Inter Font (optional) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- 100% privacy-first analytics -->
    <script data-collect-dnt="true" async src="https://scripts.simpleanalyticscdn.com/latest.js"></script>
    <noscript><img src="https://queue.simpleanalyticscdn.com/noscript.gif?collect-dnt=true" alt="" referrerpolicy="no-referrer-when-downgrade"/></noscript>

    @stack('head')
</head>
<body class="bg-slate-900 text-white font-sans">

<!-- Navbar -->
<div x-data="{ open: false }">
    <header 
        class="fixed top-0 left-0 right-0 z-50 bg-[#0f172a]/60 backdrop-blur-md border-b border-slate-800 shadow-md transition-colors duration-300"
    >
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <!-- Logo -->
        <a href="{{ route('home') }}" class="flex items-center space-x-2 group">
            <img src="{{ asset('vizzbudLogo.png') }}" alt="Vizzbud Logo" class="w-8 h-8 transition-transform group-hover:scale-105">
            <span class="text-2xl font-bold tracking-tight text-white group-hover:text-cyan-400 transition">
                Vizzbud
            </span>
        </a>

        <!-- Desktop Nav -->
        <nav class="hidden sm:flex space-x-6 text-sm font-medium items-center">
            <a href="{{ route('dive-sites.index') }}" class="text-white hover:text-cyan-400 transition">Site Map</a>
            <a href="{{ route('logbook.index') }}" class="text-white hover:text-cyan-400 transition">Dive Log</a>
            @auth
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('blog.index') }}" class="text-white hover:text-cyan-400 transition">Blog</a>
                    <a href="{{ route('admin.dashboard') }}" class="text-white hover:text-cyan-400 transition">Admin</a>
                    <a href="{{ route('admin.blog.index') }}" class="text-white hover:text-cyan-400 transition">Manage Blog</a>
                @endif
            @endauth
            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-cyan-400 hover:underline transition">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-sm text-cyan-400 hover:underline transition">Login</a>
            @endauth
        </nav>

        <!-- Hamburger Button -->
        <div class="sm:hidden">
            <button @click="open = !open" class="text-gray-400 hover:text-white focus:outline-none">
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

<!-- Full-Screen Mobile Menu -->
<div
    x-show="open"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="translate-x-full opacity-0"
    x-transition:enter-end="translate-x-0 opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-x-0 opacity-100"
    x-transition:leave-end="translate-x-full opacity-0"
    class="fixed inset-0 z-50 bg-slate-900 text-white flex flex-col sm:hidden"
    @click.away="open = false"
    style="display: none;"
>
    <!-- Close button top-right -->
    <div class="absolute top-6 right-6">
        <button @click="open = false" class="text-gray-400 hover:text-white focus:outline-none">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Centered Logo + Nav -->
    <div class="flex-grow flex flex-col justify-center items-center space-y-6 text-lg">
        <div class="text-3xl font-bold text-cyan-400">Vizzbud</div>

        <a href="{{ route('dive-sites.index') }}" class="hover:text-cyan-400 transition">Site Map</a>
        <a href="{{ route('logbook.index') }}" class="hover:text-cyan-400 transition">Dive Log</a>

        @auth
            @if (auth()->user()->isAdmin())
                <a href="{{ route('blog.index') }}" class="hover:text-cyan-400 transition">Blog</a>
                <a href="{{ route('admin.dashboard') }}" class="hover:text-cyan-400 transition">Admin</a>
                <a href="{{ route('admin.blog.index') }}" class="hover:text-cyan-400 transition">Manage Blog</a>
            @endif

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-cyan-400 hover:underline">Logout</button>
            </form>
        @else
            <a href="{{ route('login') }}" class="text-cyan-400 hover:underline">Login</a>
        @endauth
    </div>
</div>

    <!-- Main Content -->
    <main class="relative z-40 pt-[64px] min-h-screen">
        @yield('content')
    </main> 

    @stack('scripts')
</body>
</html>