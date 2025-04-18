{{-- Month Labels --}}
<div class="ml-[32px]">
    <div class="flex text-xs text-slate-400 mb-1 w-full">
        @foreach ($monthLabels as $label)
            <div class="flex-1 text-center">{{ $label }}</div>
        @endforeach
    </div>
</div>

<div class="flex">
{{-- Day Labels (aligned using grid) --}}
<div class="grid grid-rows-7 gap-[2px] text-xs text-slate-400 mr-2">
    @foreach ($dayLabels as $label)
        <div class="flex items-center h-full">{{ $label }}</div>
    @endforeach
</div>

    {{-- Stretchy Heatmap Grid --}}
    <div class="flex-1">
        <div class="flex gap-[2px] w-full">
            @foreach ($weeks as $week)
                <div class="flex-1 flex flex-col gap-[2px]">
                    @foreach ($week as $day)
                        @php
                            $count = $dailyDiveCounts[$day->format('Y-m-d')] ?? 0;
                            $color = match(true) {
                                $count >= 4 => 'bg-cyan-500',
                                $count >= 2 => 'bg-cyan-400',
                                $count === 1 => 'bg-cyan-200',
                                default => 'bg-slate-700'
                            };
                        @endphp
                        <div class="aspect-square w-full {{ $color }} rounded-sm"
                            title="{{ $day->format('Y-m-d') }}: {{ $count }} dive{{ $count === 1 ? '' : 's' }}">
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</div>