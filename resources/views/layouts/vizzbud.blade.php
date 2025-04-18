<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Vizzbud' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Mapbox -->
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css" rel="stylesheet">
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.js"></script>

    <!-- ✅ Tailwind Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Inter Font (optional) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    @stack('head')
</head>
<body class="bg-slate-900 text-white font-sans">

    <!-- Navbar -->
    <header class="bg-slate-900 border-b border-slate-800 shadow-md">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <!-- Logo -->
        <a href="{{ route('home') }}" class="text-2xl font-bold tracking-tight text-white hover:text-cyan-400 transition">
            Vizzbud
        </a>

        <!-- Navigation Links -->
        <nav class="space-x-6 text-sm font-medium flex items-center">
            <a href="{{ route('dive-sites.index') }}" class="text-white hover:text-cyan-400 transition">Site Map</a>
            <a href="{{ route('logbook.index') }}" class="text-white hover:text-cyan-400 transition">Dive Log</a>
            <a href="#" class="text-white hover:text-cyan-400 transition">About</a>

            @auth
                <!-- Logged-in user: Logout button -->
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="ml-4 text-sm text-cyan-400 hover:underline">
                        Logout
                    </button>
                </form>
            @else
                <!-- Guest: Login link -->
                <a href="{{ route('login') }}" class="ml-4 text-sm text-cyan-400 hover:underline">
                    Login
                </a>
            @endauth
        </nav>
    </div>
</header>

    <!-- Main Content -->
    <main class="min-h-screen">
        @yield('content')
    </main> 

    @stack('scripts')
</body>
</html>