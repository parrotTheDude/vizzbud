@extends('layouts.vizzbud')

@section('title', 'Admin · Dashboard | Vizzbud')
@section('meta_description', 'Manage dive sites, blog posts, users, and system settings in the Vizzbud admin dashboard.')

@push('head')
  {{-- 🚫 No indexing or crawling --}}
  <meta name="robots" content="noindex, nofollow">

  {{-- Canonical (main dashboard route) --}}
  <link rel="canonical" href="{{ route('admin.dashboard') }}">

  {{-- No Open Graph or Twitter tags (private area) --}}
  {{-- Optional internal-use only meta for dev tools or PWA display --}}
  <meta name="theme-color" content="#0f172a">
  <meta name="application-name" content="Vizzbud Admin">
  <meta name="color-scheme" content="dark">

  {{-- Optional JSON-LD (for completeness, but not indexed) --}}
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "WebApplication",
    "name": "Vizzbud Admin Dashboard",
    "url": "{{ route('admin.dashboard') }}",
    "description": "Administrative interface for managing the Vizzbud platform.",
    "creator": {
      "@type": "Organization",
      "name": "Vizzbud"
    }
  }
  </script>
@endpush

@section('content')
<section class="relative max-w-7xl mx-auto px-4 sm:px-6 py-12 sm:py-16">

  {{-- Ambient Glow --}}
  <div class="pointer-events-none absolute inset-0 -z-10">
    <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-[36rem] h-[36rem] rounded-full bg-cyan-500/10 blur-3xl"></div>
  </div>

  {{-- Header --}}
  <header class="mb-8 sm:mb-10">
    <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Admin Dashboard</h1>
    <p class="mt-2 text-white/70">Overview of users, dives, and site activity.</p>
  </header>

  {{-- Quick Actions --}}
  <h2 class="text-lg font-semibold text-white/80 mb-3">Quick Actions</h2>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-10">
    <a href="{{ route('admin.activity.index') }}"
      class="flex items-center justify-center gap-2 px-6 py-3 rounded-full bg-cyan-600 
              text-white text-sm font-medium hover:bg-cyan-500 transition-all duration-200
              shadow-md active:scale-[0.98]">
      View Activity Logs
    </a>

    <a href="{{ route('admin.divesites.index') }}"
      class="flex items-center justify-center gap-2 px-6 py-3 rounded-full bg-cyan-600 
              text-white text-sm font-medium hover:bg-cyan-500 transition-all duration-200
              shadow-md active:scale-[0.98]">
      Manage Dive Sites
    </a>

    <a href="{{ route('admin.blog.index') }}"
      class="flex items-center justify-center gap-2 px-6 py-3 rounded-full bg-white/10 
              border border-white/15 ring-1 ring-white/10 text-white/90 text-sm font-medium
              hover:bg-white/20 hover:text-white transition-all duration-200 shadow-sm
              active:scale-[0.98]">
      Manage Blog Posts
    </a>

    <a href="{{ route('admin.blog.create') }}"
      class="flex items-center justify-center gap-2 px-6 py-3 rounded-full bg-cyan-600 
              text-white text-sm font-medium hover:bg-cyan-500 transition-all duration-200
              shadow-md active:scale-[0.98]">
      Create New Post
    </a>
  </div>

  {{-- Dashboard Metrics --}}
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-12">
    <div class="rounded-2xl border border-white/10 bg-white/10 backdrop-blur-xl p-5">
      <div class="text-sm text-white/60">Dives Logged</div>
      <div class="mt-1 text-2xl font-semibold tabular-nums text-cyan-300">
        {{ number_format($metrics['dives_logged'] ?? 0) }}
      </div>
    </div>

    <div class="rounded-2xl border border-white/10 bg-white/10 backdrop-blur-xl p-5">
      <div class="text-sm text-white/60">Hours Underwater</div>
      <div class="mt-1 text-2xl font-semibold tabular-nums text-emerald-300">
        {{ number_format($metrics['hours_under'] ?? 0, 1) }}
      </div>
    </div>

    <div class="rounded-2xl border border-white/10 bg-white/10 backdrop-blur-xl p-5">
      <div class="text-sm text-white/60">Dive Sites Listed</div>
      <div class="mt-1 text-2xl font-semibold tabular-nums text-blue-300">
        {{ number_format($metrics['dive_sites'] ?? 0) }}
      </div>
    </div>

    <div class="rounded-2xl border border-white/10 bg-white/10 backdrop-blur-xl p-5">
      <div class="text-sm text-white/60">Registered Users</div>
      <div class="mt-1 text-2xl font-semibold tabular-nums text-white">
        {{ number_format($metrics['total'] ?? 0) }}
      </div>
    </div>
  </div>

  {{-- Desktop Table --}}
  <div class="hidden sm:block rounded-2xl border border-white/10 bg-white/10 backdrop-blur-xl shadow-xl overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-left text-sm">
        <thead class="bg-white/5 text-white/70 sticky top-0 z-10">
          <tr>
            <th class="px-4 py-3 font-semibold">#</th>
            <th class="px-4 py-3 font-semibold">Name</th>
            <th class="px-4 py-3 font-semibold">Email</th>
            <th class="px-4 py-3 font-semibold">Role</th>
            <th class="px-4 py-3 font-semibold">Verified</th>
            <th class="px-4 py-3 font-semibold">Created</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-white/10">
          @forelse ($users as $user)
            @php
              $role = ucfirst($user->role ?? 'user');
              $roleChip = match($user->role) {
                'admin' => 'bg-cyan-500/15 text-cyan-300 ring-cyan-400/30',
                'editor' => 'bg-amber-500/15 text-amber-300 ring-amber-400/30',
                default => 'bg-white/10 text-white/80 ring-white/10',
              };
              $v = $user->email_verified_at;
              $verifiedChip = $v
                ? 'bg-emerald-500/15 text-emerald-300 ring-emerald-400/30'
                : 'bg-rose-500/15 text-rose-300 ring-rose-400/30';
            @endphp
            <tr class="hover:bg-white/5 transition">
              <td class="px-4 py-3 tabular-nums text-white/80">{{ $user->id }}</td>
              <td class="px-4 py-3">
                <div class="font-medium">{{ $user->name }}</div>
              </td>
              <td class="px-4 py-3 text-white/80">{{ $user->email }}</td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $roleChip }}">
                  {{ $role }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $verifiedChip }}">
                  {{ $v ? 'Verified' : 'Unverified' }}
                </span>
              </td>
              <td class="px-4 py-3 text-white/80">
                {{ optional($user->created_at)->format('M j, Y') }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-4 py-8 text-center text-white/60">
                No users found.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Pagination --}}
    @if($users instanceof \Illuminate\Pagination\LengthAwarePaginator)
      <div class="px-4 py-3 border-t border-white/10 bg-white/5">
        {{ $users->withQueryString()->links() }}
      </div>
    @endif
  </div>

  {{-- Mobile Cards --}}
  <div class="space-y-4 sm:hidden">
    @foreach ($users as $user)
      @php
        $role = ucfirst($user->role ?? 'user');
        $roleChip = match($user->role) {
          'admin' => 'bg-cyan-500/15 text-cyan-300 ring-cyan-400/30',
          'editor' => 'bg-amber-500/15 text-amber-300 ring-amber-400/30',
          default => 'bg-white/10 text-white/80 ring-white/10',
        };
        $v = $user->email_verified_at;
        $verifiedChip = $v
          ? 'bg-emerald-500/15 text-emerald-300 ring-emerald-400/30'
          : 'bg-rose-500/15 text-rose-300 ring-rose-400/30';
      @endphp

      <div class="rounded-xl border border-white/10 bg-white/10 p-4 shadow-sm">
        <div class="flex justify-between items-start mb-1">
          <h3 class="font-semibold text-white">{{ $user->name }}</h3>
          <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 {{ $roleChip }}">
            {{ $role }}
          </span>
        </div>
        <p class="text-sm text-white/70">{{ $user->email }}</p>
        <div class="flex justify-between items-center mt-2 text-xs text-white/60">
          <span class="inline-flex items-center rounded-full px-2 py-0.5 font-semibold ring-1 {{ $verifiedChip }}">
            {{ $v ? 'Verified' : 'Unverified' }}
          </span>
          <span>{{ optional($user->created_at)->format('M j, Y') }}</span>
        </div>
      </div>
    @endforeach

    <div class="pt-4">
      {{ $users->links() }}
    </div>
  </div>

</section>
@endsection