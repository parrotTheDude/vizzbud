{{-- Responsive scroll wrapper (mobile scrolls horizontally, desktop fluid) --}}
<div class="relative -mx-4 sm:mx-0 px-4 sm:px-0 overflow-x-auto sm:overflow-visible scroll-smooth overscroll-x-contain touch-pan-x">

  {{-- Inner track: prevent squish on small screens, fluid on ≥sm --}}
  <div class="inline-block align-top min-w-[700px] sm:min-w-0 w-full">

    {{-- MONTH LABELS (top row) --}}
    <div class="grid items-center mb-1"
         style="grid-template-columns: 32px repeat({{ count($weeks) }}, minmax(0,1fr));">
      {{-- left spacer to align with day labels column --}}
      <div></div>

      {{-- months aligned to week columns --}}
      @foreach ($monthLabels as $label)
        <div class="text-center text-xs text-slate-400">{{ $label }}</div>
      @endforeach
    </div>

    {{-- GRID: left = day labels (fixed), right = heatmap (weeks x days) --}}
    <div class="grid"
         style="grid-template-columns: 32px 1fr;">
      {{-- Day Labels (fixed 7 rows) --}}
      <div class="grid grid-rows-7 gap-[2px] mr-2">
        @foreach ($dayLabels as $label)
          <div class="flex items-center h-full text-xs text-slate-400">{{ $label }}</div>
        @endforeach
      </div>

      {{-- Heatmap (weeks as columns, days as rows) --}}
      <div class="grid gap-[2px]"
           style="grid-template-columns: repeat({{ count($weeks) }}, minmax(0,1fr));">
        @foreach ($weeks as $week)
          {{-- Each week is a column with 7 day squares --}}
          <div class="grid grid-rows-7 gap-[2px]">
            @foreach ($week as $day)
              @php
                $count = $dailyDiveCounts[$day->format('Y-m-d')] ?? 0;
                $color = match(true) {
                  $count >= 4 => 'bg-cyan-500',
                  $count >= 2 => 'bg-cyan-400',
                  $count === 1 => 'bg-cyan-200',
                  default => 'bg-slate-700',
                };
              @endphp
              <div class="aspect-square w-full rounded-sm {{ $color }}"
                   title="{{ $day->format('Y-m-d') }}: {{ $count }} dive{{ $count === 1 ? '' : 's' }}">
              </div>
            @endforeach
          </div>
        @endforeach
      </div>
    </div>

  </div>
</div>