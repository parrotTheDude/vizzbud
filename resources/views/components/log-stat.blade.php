@props(['label', 'value'])

<div class="bg-slate-800 p-4 rounded-xl shadow text-center space-y-1">
    {{-- Optional icon slot --}}
    @isset($icon)
        <div class="text-xl sm:text-base text-cyan-400">
            {{ $icon }}
        </div>
    @endisset

    <h2 class="text-sm text-slate-400">{{ $label }}</h2>
    <p class="text-2xl font-bold text-white">{{ $value }}</p>
</div>