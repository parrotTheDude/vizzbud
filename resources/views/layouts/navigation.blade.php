<nav x-data="{ open: false }" class="bg-slate-900 border-b border-slate-800 shadow">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        {{-- Logo --}}
        <a href="{{ route('dashboard') }}" class="text-xl font-bold text-white hover:text-cyan-400 transition">
            Vizzbud
        </a>

        {{-- Desktop Navigation --}}
        <div class="hidden sm:flex items-center space-x-6">
            <a href="{{ route('dive-sites.index') }}" class="text-white hover:text-cyan-400 text-sm">Site Map</a>
            <a href="{{ route('logbook.index') }}" class="text-white hover:text-cyan-400 text-sm">Dive Log</a>
            <a href="#" class="text-white hover:text-cyan-400 text-sm">About</a>

            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-cyan-400 hover:underline text-sm">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-cyan-400 hover:underline text-sm">Login</a>
            @endauth
        </div>

        {{-- Hamburger (Mobile) --}}
        <div class="sm:hidden">
            <button @click="open = !open" class="text-gray-400 hover:text-white focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 6h16M4 12h16M4 18h16" />
                    <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Mobile Nav --}}
    <div x-show="open" x-transition class="sm:hidden px-6 pb-4 space-y-2">
        <a href="{{ route('dive-sites.index') }}" class="block text-white hover:text-cyan-400 text-sm">Site Map</a>
        <a href="{{ route('logbook.index') }}" class="block text-white hover:text-cyan-400 text-sm">Dive Log</a>
        <a href="#" class="block text-white hover:text-cyan-400 text-sm">About</a>

        @auth
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-cyan-400 hover:underline text-sm">Logout</button>
            </form>
        @else
            <a href="{{ route('login') }}" class="text-cyan-400 hover:underline text-sm">Login</a>
        @endauth
    </div>
</nav>