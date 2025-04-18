@extends('layouts.vizzbud')

@section('content')
<section class="max-w-md mx-auto px-6 py-16">
    <h1 class="text-3xl font-bold text-white mb-6 text-center">🧜‍♂️ Create Your Vizzbud Account</h1>

    <form method="POST" action="{{ route('register') }}" class="bg-slate-800 rounded-xl p-6 space-y-4 shadow">
        @csrf

        <!-- Name -->
        <div>
            <label for="name" class="block mb-1 text-sm text-slate-300">Name</label>
            <input id="name" name="name" type="text" required autofocus
                   value="{{ old('name') }}"
                   class="w-full p-2 rounded text-black" />
            @error('name')
                <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Email -->
        <div>
            <label for="email" class="block mb-1 text-sm text-slate-300">Email</label>
            <input id="email" name="email" type="email" required
                   value="{{ old('email') }}"
                   class="w-full p-2 rounded text-black" />
            @error('email')
                <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block mb-1 text-sm text-slate-300">Password</label>
            <input id="password" name="password" type="password" required
                   class="w-full p-2 rounded text-black" />
            @error('password')
                <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block mb-1 text-sm text-slate-300">Confirm Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                   class="w-full p-2 rounded text-black" />
            @error('password_confirmation')
                <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-between mt-6 text-sm">
            <a href="{{ route('login') }}" class="text-cyan-400 hover:underline">
                Already have an account?
            </a>

            <button type="submit"
                class="bg-cyan-500 hover:bg-cyan-600 text-white font-semibold py-2 px-4 rounded transition">
                ✅ Register
            </button>
        </div>
    </form>
</section>
@endsection