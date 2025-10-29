@php
$steps = [
  ['label' => 'Add a profile picture', 'done' => !empty($user->avatar_url)],
  ['label' => 'Add your certification or bio', 'done' => !empty($user->certification) || !empty($user->bio)],
  ['label' => 'Log your first dive', 'done' => ($stats['total_dives'] ?? 0) > 0],
  ['label' => 'Add your gear setup', 'done' => $user->suit_type || $user->tank_type || $user->weight_used],
];
$totalSteps = count($steps);
$completedSteps = collect($steps)->where('done', true)->count();
$progress = round(($completedSteps / $totalSteps) * 100);
@endphp

<div
  x-data="welcomeWidget({{ $progress }})"
  x-init="initConfetti()"
  x-show="visible"
  x-transition.opacity.duration.500ms
  class="relative bg-slate-800/60 border border-slate-700 rounded-2xl p-6 sm:p-8 space-y-5 shadow-md"
>
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
      <h2 class="text-xl font-semibold text-cyan-400">
        👋 Welcome, {{ $user->name }}!
      </h2>
      <p class="text-slate-300 text-sm">
        Let’s finish setting up your profile to unlock stats, badges, and your personal dive map.
      </p>
    </div>
    <div class="text-right">
      <div class="text-sm text-slate-400 font-medium" x-text="progress + '%'"></div>
      <div class="h-2 bg-slate-700 rounded-full w-32 overflow-hidden mt-1">
        <div class="h-full bg-cyan-500 rounded-full transition-all duration-500"
             :style="{ width: progress + '%' }"></div>
      </div>
    </div>
  </div>

  {{-- Checklist --}}
  <ul class="text-sm text-slate-300 grid sm:grid-cols-2 gap-y-2 sm:gap-y-1 gap-x-6 mt-4">
    @foreach($steps as $step)
      <li class="flex items-center gap-2">
        @if($step['done'])
          <span class="text-emerald-400">✔</span>
          <span class="line-through text-slate-500">{{ $step['label'] }}</span>
        @else
          <span class="text-slate-500">◻</span>
          <span>{{ $step['label'] }}</span>
        @endif
      </li>
    @endforeach
  </ul>

  {{-- Action buttons --}}
  <div class="flex flex-wrap gap-3 pt-4 border-t border-slate-700 mt-4">
    @if(empty($user->avatar_url))
      <a href="{{ route('profile.edit') }}" class="bg-cyan-600 hover:bg-cyan-500 text-white px-4 py-2 rounded-full text-sm font-medium transition">
        Add Profile Photo
      </a>
    @elseif(empty($user->certification) && empty($user->bio))
      <a href="{{ route('profile.edit') }}" class="bg-cyan-600 hover:bg-cyan-500 text-white px-4 py-2 rounded-full text-sm font-medium transition">
        Complete Bio
      </a>
    @elseif(($stats['total_dives'] ?? 0) == 0)
      <a href="{{ route('dive-logs.create') }}" class="bg-cyan-600 hover:bg-cyan-500 text-white px-4 py-2 rounded-full text-sm font-medium transition">
        Log Your First Dive
      </a>
    @elseif(!$user->suit_type && !$user->tank_type)
      <a href="{{ route('gear.index') }}" class="bg-cyan-600 hover:bg-cyan-500 text-white px-4 py-2 rounded-full text-sm font-medium transition">
        Add Gear Setup
      </a>
    @endif
  </div>

  {{-- 🎉 Success Banner --}}
  <div x-show="showBanner"
       x-transition.opacity.duration.400ms
       class="absolute inset-0 flex flex-col items-center justify-center bg-cyan-600/90 text-white rounded-2xl text-center p-6">
    <h3 class="text-2xl font-bold mb-2">🎉 Profile Complete!</h3>
    <p class="text-sm text-white/90">You’ve unlocked your full dashboard experience.</p>
  </div>
</div>

{{-- Alpine + Confetti Script --}}
@once
<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('welcomeWidget', (progress) => ({
    progress,
    visible: true,
    showBanner: false,
    initConfetti() {
      if (this.progress >= 100) {
        this.showCelebration();
      }
    },
    showCelebration() {
      this.showBanner = true;
      this.launchConfetti();
      setTimeout(() => {
        this.visible = false;
      }, 3000);
    },
    launchConfetti() {
      // Simple confetti burst using canvas API
      const duration = 2 * 1000;
      const end = Date.now() + duration;
      const colors = ['#06b6d4', '#2dd4bf', '#a5f3fc'];

      (function frame() {
        const timeLeft = end - Date.now();
        if (timeLeft <= 0) return;
        const particleCount = 5 * (timeLeft / duration);
        confetti({
          particleCount,
          spread: 70,
          origin: { y: 0.6 },
          colors,
        });
        requestAnimationFrame(frame);
      })();
    }
  }));
});
</script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
@endonce