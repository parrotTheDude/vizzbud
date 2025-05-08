@extends('layouts.vizzbud')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-12 bg-gray-50">
    <div class="w-full max-w-md bg-white shadow-xl rounded-2xl p-8 space-y-6" 
         x-data="passwordForm('{{ old('email', $request->email) }}', '{{ $request->route('token') }}')">
        
        <div class="text-center">
            <h2 class="text-2xl font-bold text-gray-800">🔒 Reset Your Password</h2>
            <p class="mt-1 text-sm text-gray-500">Create a strong new password below.</p>
        </div>

        <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="token" :value="token">

            <!-- Email -->
            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="email" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div class="relative">
                <x-input-label for="password" :value="__('New Password')" />
                <input :type="showPassword ? 'text' : 'password'" name="password" x-model="password"
                       id="password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                <button type="button" @click="showPassword = !showPassword"
                        class="absolute right-2 top-9 text-sm text-gray-500 hover:text-gray-700">
                    <span x-text="showPassword ? '🙈' : '👁️'"></span>
                </button>
            </div>

            <!-- Checklist -->
            <ul class="text-sm mt-2 space-y-1">
                <template x-for="rule in rules" :key="rule.text">
                    <li class="flex items-center space-x-2 group">
                        <span x-show="rule.valid" class="text-green-600 transition">&#10003;</span>
                        <span x-show="!rule.valid" class="text-gray-400 transition">&#8226;</span>
                        <span :class="rule.valid ? 'text-green-600' : 'text-gray-500'" 
                              x-text="rule.text" class="group-hover:underline cursor-default"
                              :title="rule.tooltip">
                        </span>
                    </li>
                </template>
            </ul>

            <!-- Confirm Password -->
            <div class="relative">
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <input :type="showPassword ? 'text' : 'password'" name="password_confirmation" x-model="confirm"
                       id="password_confirmation" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required autocomplete="new-password" />
                <p class="text-sm mt-1" 
                   :class="confirm ? (confirm === password ? 'text-green-600' : 'text-red-600') : 'text-gray-400'">
                    <template x-if="confirm">
                        <span x-text="confirm === password ? '✅ Passwords match' : '❌ Passwords do not match'"></span>
                    </template>
                </p>
            </div>

            <!-- Submit -->
            <div class="pt-4">
                <x-primary-button class="w-full justify-center">
                    {{ __('Reset Password') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</div>

<script>
function passwordForm(email, token) {
    return {
        password: '',
        confirm: '',
        email: email,
        token: token,
        showPassword: false,
        get rules() {
            return [
                { text: 'Minimum 8 characters', valid: this.password.length >= 8, tooltip: 'Use at least 8 characters' },
                { text: 'One lowercase letter', valid: /[a-z]/.test(this.password), tooltip: 'Include a lowercase letter (a-z)' },
                { text: 'One uppercase letter', valid: /[A-Z]/.test(this.password), tooltip: 'Include an uppercase letter (A-Z)' },
                { text: 'One number', valid: /[0-9]/.test(this.password), tooltip: 'Include at least one number (0-9)' },
                { text: 'One special character', valid: /[@$!%*#?&]/.test(this.password), tooltip: 'Include a symbol like @ $ ! % *' },
            ];
        }
    }
}
</script>
@endsection