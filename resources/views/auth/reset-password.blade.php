@extends('layouts.vizzbud')

@section('content')
<section class="max-w-md mx-auto px-6 py-16">
    <h1 class="text-3xl font-bold text-white mb-6 text-center">🔒 Reset Your Password</h1>

    <form method="POST" action="{{ route('password.store') }}" 
          x-data="passwordForm('{{ old('email', $request->email) }}', '{{ $request->route('token') }}')" 
          class="bg-slate-800 rounded-xl p-6 space-y-5 shadow">
        @csrf

        <!-- Hidden Token & Email -->
        <input type="hidden" name="token" :value="token">
        <input type="hidden" name="email" x-model="email" />

        <!-- New Password -->
        <div class="relative">
            <label for="password" class="block mb-1 text-sm text-slate-300">New Password</label>
            <input :type="showPassword ? 'text' : 'password'" name="password" x-model="password"
                   id="password" class="w-full p-2 rounded text-black" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="text-red-400 text-sm mt-1" />
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block mb-1 text-sm text-slate-300">Confirm Password</label>
            <input :type="showPassword ? 'text' : 'password'" name="password_confirmation" x-model="confirm"
                   id="password_confirmation" class="w-full p-2 rounded text-black" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="text-red-400 text-sm mt-1" />
        </div>

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

        <!-- Submit -->
        <div class="pt-2">
            <button type="submit"
                class="bg-cyan-500 hover:bg-cyan-600 text-white font-semibold py-2 px-4 rounded transition w-full">
                🔄 Reset Password
            </button>
        </div>
    </form>

    <p class="text-center text-sm text-slate-400 mt-4">
        Changed your mind?
        <a href="{{ route('login') }}" class="text-cyan-400 hover:underline">Back to login</a>
    </p>
</section>

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
                { text: 'One special character', valid: /[@$!%*#?&\-]/.test(this.password), tooltip: 'Include a symbol like @ $ ! % * -' },
                { text: 'Passwords match', valid: this.confirm === this.password && this.password !== '', tooltip: 'Both password fields must match' },
            ];
        }
    }
}
</script>
@endsection