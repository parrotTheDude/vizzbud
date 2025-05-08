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

        <!-- Password and Confirm Password with Rules -->
        <div x-data="passwordForm()" class="space-y-4">
            <!-- Password -->
            <div class="relative">
                <label for="password" class="block mb-1 text-sm text-slate-300">Password</label>
                <input id="password" name="password" type="password" x-model="password"
                    class="w-full p-2 rounded text-black" required autocomplete="new-password" />
                @error('password')
                    <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div class="relative">
                <label for="password_confirmation" class="block mb-1 text-sm text-slate-300">Confirm Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                    x-model="confirm" class="w-full p-2 rounded text-black" required autocomplete="new-password" />
            </div>

            <!-- Checklist -->
            <ul class="text-sm mt-2 space-y-1 text-slate-300">
                <template x-for="rule in rules" :key="rule.text">
                    <li class="flex items-center space-x-2 group">
                        <span x-show="rule.valid" class="text-green-400">&#10003;</span>
                        <span x-show="!rule.valid" class="text-slate-500">&#8226;</span>
                        <span :class="rule.valid ? 'text-green-400' : 'text-slate-400'" 
                            x-text="rule.text" class="group-hover:underline cursor-default"
                            :title="rule.tooltip">
                        </span>
                    </li>
                </template>
            </ul>
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
<script>
function passwordForm() {
    return {
        password: '',
        confirm: '',
        get rules() {
            return [
                { text: 'Minimum 8 characters', valid: this.password.length >= 8, tooltip: 'Use at least 8 characters' },
                { text: 'One lowercase letter', valid: /[a-z]/.test(this.password), tooltip: 'Include a lowercase letter (a-z)' },
                { text: 'One uppercase letter', valid: /[A-Z]/.test(this.password), tooltip: 'Include an uppercase letter (A-Z)' },
                { text: 'One number', valid: /[0-9]/.test(this.password), tooltip: 'Include at least one number (0-9)' },
                { text: 'One special character', valid: /[!@#$%^&*(),.?":{}|<>_\-]/.test(this.password), tooltip: 'Include a symbol like ! @ # $ etc.' },
                { text: 'Passwords match', valid: this.confirm === this.password && this.confirm !== '', tooltip: 'Confirm must match password' },
            ];
        }
    }
}
</script>
@endsection