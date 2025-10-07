@extends('layouts.vizzbud')

@section('title', 'Admin · Dashboard')

@section('content')
<section class="relative max-w-7xl mx-auto px-4 sm:px-6 py-12 sm:py-16">

  {{-- ambient glow --}}
  <div class="pointer-events-none absolute inset-0 -z-10">
    <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-[36rem] h-[36rem] rounded-full bg-cyan-500/10 blur-3xl"></div>
  </div>

  {{-- header --}}
  <header class="mb-8 sm:mb-10">
    <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Admin Dashboard</h1>
    <p class="mt-2 text-white/70">Manage users at a glance.</p>
  </header>

  {{-- overview / chips --}}
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    @php
      $total = $users instanceof \Illuminate\Pagination\LengthAwarePaginator ? $users->total() : $users->count();
      $verified = $users->filter(fn($u) => !is_null($u->email_verified_at))->count();
      $admins = $users->filter(fn($u) => (string)($u->role ?? '') === 'admin')->count();
    @endphp

    <div class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-white/10 backdrop-blur-xl p-5">
      <div class="text-sm text-white/60">Total users</div>
      <div class="mt-1 text-2xl font-semibold tabular-nums">{{ number_format($total) }}</div>
    </div>

    <div class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-white/10 backdrop-blur-xl p-5">
      <div class="text-sm text-white/60">Email verified</div>
      <div class="mt-1 text-2xl font-semibold tabular-nums text-emerald-300">{{ number_format($verified) }}</div>
    </div>

    <div class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-white/10 backdrop-blur-xl p-5">
      <div class="text-sm text-white/60">Admins</div>
      <div class="mt-1 text-2xl font-semibold tabular-nums text-cyan-300">{{ number_format($admins) }}</div>
    </div>
  </div>

  {{-- toolbar --}}
  <div class="mb-6 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
    <div class="text-white/70 text-sm">
      Welcome, admin. Below is a list of all registered users.
    </div>

    {{-- simple search (GET ?q=) --}}
    <form method="GET" class="w-full sm:w-auto">
      <div class="flex items-center gap-2 rounded-xl bg-white/10 border border-white/10 ring-1 ring-white/10 backdrop-blur-xl px-3 py-2">
        <svg class="h-4 w-4 text-white/60" viewBox="0 0 24 24" fill="none">
          <path d="M21 21l-4.3-4.3M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <input
          type="text"
          name="q"
          value="{{ request('q') }}"
          placeholder="Search by name or email…"
          class="bg-transparent outline-none placeholder-white/40 text-sm w-full"
          />
      </div>
    </form>
  </div>

  {{-- table card --}}
  <div class="rounded-2xl border border-white/10 ring-1 ring-white/10 bg-white/10 backdrop-blur-xl shadow-xl overflow-hidden">
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
                <div class="text-xs text-white/50 sm:hidden">{{ $user->email }}</div>
              </td>
              <td class="px-4 py-3 hidden sm:table-cell text-white/80">{{ $user->email }}</td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $roleChip }}">
                  {{ $role }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $verifiedChip }}">
                  @if ($v)
                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 .01 1.4l-7.2 7.3a1 1 0 0 1-1.42.01L4.9 11.5a1 1 0 1 1 1.4-1.42l2.2 2.2 6.5-6.6a1 1 0 0 1 1.4.01Z" clip-rule="evenodd"/></svg>
                    Verified
                  @else
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M18.3 5.7a1 1 0 0 1 0 1.4L13.4 12l4.9 4.9a1 1 0 1 1-1.4 1.4L12 13.4l-4.9 4.9a1 1 0 1 1-1.4-1.4L10.6 12 5.7 7.1a1 1 0 0 1 1.4-1.4L12 10.6l4.9-4.9a1 1 0 0 1 1.4 0Z"/></svg>
                    Unverified
                  @endif
                </span>
              </td>
              <td class="px-4 py-3 text-white/80">
                {{ optional($user->created_at)->format('M j, Y') }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-4 py-8 text-center text-white/60">No users found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- footer / pagination --}}
    @if($users instanceof \Illuminate\Pagination\LengthAwarePaginator)
      <div class="px-4 py-3 border-t border-white/10 bg-white/5">
        {{ $users->withQueryString()->links() }}
      </div>
    @endif
  </div>

</section>
@endsection