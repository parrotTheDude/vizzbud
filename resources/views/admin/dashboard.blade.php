@extends('layouts.vizzbud')

@section('title', 'Admin Dashboard')

@section('content')
<section class="max-w-6xl mx-auto px-4 sm:px-6 py-12">
    <h1 class="text-3xl font-bold text-white mb-6">🛠️ Admin Dashboard</h1>

    <div class="bg-slate-800 p-6 rounded-xl shadow text-white mb-8">
        <p class="text-slate-300">Welcome, admin. Below is a list of all registered users.</p>
    </div>

    <div class="bg-slate-800 rounded-xl shadow overflow-x-auto">
        <table class="min-w-full text-left text-sm text-white">
            <thead class="bg-slate-900 text-slate-400">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Verified</th>
                    <th class="px-4 py-3">Created</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr class="border-b border-slate-700 hover:bg-slate-700/50 transition">
                        <td class="px-4 py-3">{{ $user->id }}</td>
                        <td class="px-4 py-3">{{ $user->name }}</td>
                        <td class="px-4 py-3">{{ $user->email }}</td>
                        <td class="px-4 py-3">{{ ucfirst($user->role) }}</td>
                        <td class="px-4 py-3">
                            @if ($user->email_verified_at)
                                <span class="text-green-400">✔</span>
                            @else
                                <span class="text-red-400">✖</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $user->created_at->format('M j, Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection