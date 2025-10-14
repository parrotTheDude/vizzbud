@extends('layouts.vizzbud')

@section('title', $diveSite->name . ' | Dive Site Info')
@section('meta_description', Str::limit(strip_tags($diveSite->description), 160))

@php
  use App\Helpers\CompassHelper;
  $c = $diveSite->latestCondition;
  $status = strtolower(optional($c)->status ?? '');
  $accent = match($status) {
      'green'  => 'emerald',
      'yellow' => 'amber',
      'red'    => 'rose',
      default  => 'cyan',
  };
@endphp

@push('head')
  <link href="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css" rel="stylesheet">
@endpush

@section('content')
<section class="relative">

  {{-- 🌅 Hero --}}
  <div class="relative mb-0">
    @php
      $featuredPhoto = $diveSite->photos()->where('is_featured', true)->first();
      $heroImage = $featuredPhoto ? asset($featuredPhoto->image_path) : asset('images/divesites/default.webp');
      $photoArtist = null;
      $photoCreditLink = null;

      if ($featuredPhoto) {
          if ($featuredPhoto->artist_instagram) {
              $photoArtist = '@' . $featuredPhoto->artist_instagram;
              $photoCreditLink = 'https://www.instagram.com/' . $featuredPhoto->artist_instagram;
          } elseif ($featuredPhoto->artist_name) {
              $photoArtist = $featuredPhoto->artist_name;
          }
      }
    @endphp

    {{-- Background Image --}}
    <img 
      src="{{ $heroImage }}" 
      alt="{{ $diveSite->name }} featured image"
      class="w-full h-[320px] sm:h-[460px] object-cover rounded-b-3xl border border-white/20 shadow-2xl"
    />

    {{-- Overlay Gradient --}}
    <div class="absolute inset-0 rounded-b-3xl bg-gradient-to-t 
                from-slate-900/85 via-slate-900/20 to-transparent"></div>

    {{-- Centered Title + Location + Credit --}}
    <div class="absolute bottom-0 left-0 right-0 pb-10 flex flex-col items-center text-center px-6">
      <h1 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight drop-shadow-lg mb-2">
        {{ $diveSite->name }}
      </h1>
      <p class="text-slate-300 text-sm sm:text-base font-medium mb-1">
        {{ $diveSite->region }}, {{ $diveSite->country }}
      </p>

      {{-- 📸 Image Credit --}}
      @if($featuredPhoto && ($photoArtist || $photoCreditLink))
        <p class="text-[11px] sm:text-[12px] text-white/60">
          Photo by 
          @if($photoCreditLink)
            <a href="{{ $photoCreditLink }}"
              target="_blank"
              rel="noopener noreferrer"
              class="underline hover:text-white font-medium">
              {{ $photoArtist }}
            </a>
          @else
            <span class="font-medium">{{ $photoArtist }}</span>
          @endif
        </p>
      @endif
      
    </div>
  </div>

  {{-- 🌊 Compact Info Bar --}}
  <section class="w-full flex justify-center -mt-6 mb-10 px-4 sm:px-0">
    <div class="inline-flex flex-wrap items-center justify-center
                bg-white/10 backdrop-blur-xl border border-white/10 ring-1 ring-white/5 shadow-md
                rounded-full divide-x divide-white/10 overflow-hidden">

      @php
        $items = [
          ['icon' => 'diver.svg', 'label' => $diveSite->suitability],
          ['icon' => $diveSite->dive_type === 'boat' ? 'boat.svg' : 'beach.svg', 'label' => ucfirst($diveSite->dive_type)],
          ['icon' => 'pool-depth.svg', 'label' => 'Avg ' . number_format($diveSite->avg_depth, 0) . 'm'],
          ['icon' => 'under-water.svg', 'label' => 'Max ' . number_format($diveSite->max_depth, 0) . 'm'],
        ];
      @endphp

      @foreach ($items as $item)
        <div class="flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 px-4 py-2 sm:px-5 sm:py-2">
          <img src="/icons/{{ $item['icon'] }}" class="w-4 h-4 invert opacity-80" alt="">
          <span class="text-[13px] sm:text-[12.5px] text-white/90 font-medium tracking-tight">
            {{ $item['label'] }}
          </span>
        </div>
      @endforeach

    </div>
  </section>

  {{-- 🌊 Conditions + Forecast (Stacked Layout, beginner-friendly) --}}
  <section class="max-w-5xl mx-auto mt-10 mb-16 px-4 sm:px-8 space-y-10 sm:space-y-8">

    {{-- 🌊 Current Conditions --}}
    @if($c)
      @php 
        $status = strtolower(optional($diveSite->latestCondition)->status ?? '');
        $classes = match ($status) {
            'green'  => 'bg-emerald-500/20 border-emerald-400/40 text-emerald-100',
            'yellow' => 'bg-amber-400/25 border-amber-300/40 text-amber-100',
            'red'    => 'bg-rose-500/25 border-rose-400/40 text-rose-100',
            default  => 'bg-slate-500/20 border-slate-400/40 text-slate-200',
        };
      @endphp

      <div class="rounded-3xl bg-white/10 backdrop-blur-2xl border border-white/15 
                  ring-1 ring-{{ $accent }}-500/20 shadow-xl p-6 sm:p-8">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5 text-center sm:text-left">
          <h2 class="text-white font-semibold text-lg flex items-center gap-2 justify-center sm:justify-start">
            Current Conditions
          </h2>

          {{-- Live Status Pill --}}
          <div class="flex items-center justify-center sm:justify-end gap-2 rounded-full px-3 py-1.5 
                      border {{ $classes }} backdrop-blur-sm shadow-sm w-fit mx-auto sm:mx-0">
            <span class="relative inline-flex w-2.5 h-2.5">
              <span class="absolute inset-0 rounded-full opacity-50 animate-ping
                {{ $status === 'green' ? 'bg-emerald-300' : ($status === 'yellow' ? 'bg-amber-300' : ($status === 'red' ? 'bg-rose-300' : 'bg-slate-300')) }}"></span>
              <span class="relative inline-flex w-2.5 h-2.5 rounded-full
                {{ $status === 'green' ? 'bg-emerald-400' : ($status === 'yellow' ? 'bg-amber-400' : ($status === 'red' ? 'bg-rose-400' : 'bg-slate-400')) }}"></span>
            </span>

            <span class="text-xs uppercase tracking-wide font-medium">Live</span>
            <span class="text-[13px] font-semibold">
              @if ($status === 'green') Good now
              @elseif ($status === 'yellow') Fair now
              @elseif ($status === 'red') Poor now
              @else Unavailable
              @endif
            </span>
          </div>
        </div>

        {{-- Metrics grid (3 on mobile → 6 on desktop) --}}
        <div class="grid grid-cols-3 lg:grid-cols-6 gap-3 text-sm mb-2">
          @php
            $metrics = [
              ['wave.svg', 'Swell', $c->wave_height ? number_format($c->wave_height, 1).' m' : '–', 'Wave height in metres'],
              ['compass.svg', 'Dir', $c->wave_direction !== null ? CompassHelper::fromDegrees($c->wave_direction) : '—', 'Wave direction'],
              ['tools-and-utensils.svg', 'Period', $c->wave_period ? number_format($c->wave_period, 0).' s' : '–', 'Time between waves (seconds)'],
              ['wind.svg', 'Wind', $c->wind_speed ? number_format($c->wind_speed * 1.94384, 0).' kn' : '–', 'Surface wind speed in knots'],
              ['temperature.svg', 'Water', $c->water_temperature ? number_format($c->water_temperature, 1).' °C' : '–', 'Water temperature at depth'],
              ['temperature.svg', 'Air', $c->air_temperature ? number_format($c->air_temperature, 1).' °C' : '–', 'Air temperature above surface'],
            ];
          @endphp

          @foreach($metrics as [$icon, $label, $value, $hint])
            <div class="flex flex-col items-center justify-center p-3 rounded-xl 
                        bg-white/5 border border-white/10 text-slate-200 hover:bg-white/10 transition"
                title="{{ $hint }}">
              <img src="/icons/{{ $icon }}" class="w-5 h-5 invert mb-1 opacity-90" alt="{{ $label }}">
              <span class="text-xs uppercase tracking-wide text-white/60">{{ $label }}</span>
              <strong class="text-sm text-white mt-0.5">{{ $value }}</strong>
            </div>
          @endforeach
        </div>
      </div>
    @endif

    {{-- 🌅 3-Day Diveability Forecast --}}
    @if(!empty($daypartForecasts ?? []))
      <div class="rounded-3xl bg-white/10 backdrop-blur-2xl border border-white/15 
                  ring-1 ring-{{ $accent }}-500/20 shadow-xl p-6 sm:p-8 w-full">
        <h2 class="text-white font-semibold text-lg text-center mb-3">3-Day Dive Forecast</h2>
        <p class="text-center text-xs text-white/60 mb-6">
          Each segment shows expected dive conditions for different parts of the day.
        </p>

        {{-- Forecast Grid (now full width like the other cards) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-8">
          @foreach ($daypartForecasts as $day)
            @php $date = \Carbon\Carbon::parse($day['date']); @endphp

            {{-- Forecast Card --}}
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 flex flex-col items-center 
                        text-center hover:bg-white/10 transition h-full">
              
              {{-- Header --}}
              <div class="mb-3">
                <p class="text-white font-semibold text-sm leading-tight">
                  {{ $date->isToday() ? 'Today' : $date->format('D') }}
                </p>
                <p class="text-[11px] text-white/60 leading-tight">{{ $date->format('j M') }}</p>
              </div>

              {{-- Day-parts row (icon + label + time) --}}
              <div class="flex justify-center gap-2 w-full">
                @php
                  $parts = [
                    ['label' => 'Morning', 'time' => '6am – 11am', 'key' => 'morning', 'icon' => 'morning.svg'],
                    ['label' => 'Afternoon', 'time' => '12pm – 4pm', 'key' => 'afternoon', 'icon' => 'afternoon.svg'],
                    ['label' => 'Night', 'time' => '5pm – 9pm', 'key' => 'night', 'icon' => 'night.svg'],
                  ];
                @endphp

                @foreach ($parts as $part)
                  @php
                    $status = strtolower($day[$part['key']] ?? 'unknown');
                    $classes = match ($status) {
                        'green'  => 'bg-emerald-400/20 border-emerald-400/30 text-emerald-100',
                        'yellow' => 'bg-amber-400/20 border-amber-300/30 text-amber-100',
                        'red'    => 'bg-rose-500/20 border-rose-400/30 text-rose-100',
                        default  => 'bg-slate-500/20 border-slate-400/30 text-slate-200',
                    };
                  @endphp

                  <div class="flex flex-col items-center justify-center gap-1.5 rounded-lg 
                              px-3 py-2 text-[11px] font-medium border {{ $classes }} flex-1 
                              backdrop-blur-sm shadow-sm hover:bg-white/10 transition"
                      title="{{ ucfirst($part['label']) }} conditions ({{ $part['time'] }})">
                    <img src="/icons/{{ $part['icon'] }}" class="w-4 h-4 invert opacity-90 mb-0.5" alt="{{ $part['label'] }}">
                    <p class="capitalize font-semibold text-white">{{ $part['label'] }}</p>
                    <p class="text-[10px] text-white/70">{{ $part['time'] }}</p>
                  </div>
                @endforeach
              </div>
            </div>
          @endforeach
        </div>

        {{-- 🌈 Integrated Legend --}}
        <div class="rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm 
                    shadow-inner p-5 text-center">
          <h3 class="text-white font-semibold text-lg text-center mb-3">Dive Condition Key</h3>

          <div class="flex flex-col sm:flex-row justify-center items-stretch gap-4 sm:gap-8 
                      text-[13px] text-white/80 leading-relaxed max-w-3xl mx-auto">

            {{-- Green --}}
            <div class="flex flex-col items-center text-center gap-1.5">
              <span class="w-3 h-3 bg-emerald-400 rounded-full shadow-sm"></span>
              <p class="font-semibold text-white text-sm">Great diving</p>
              <p class="text-xs text-white/70 max-w-[220px]">Calm water, clear visibility, and easy conditions.</p>
            </div>

            {{-- Yellow --}}
            <div class="flex flex-col items-center text-center gap-1.5">
              <span class="w-3 h-3 bg-amber-400 rounded-full shadow-sm"></span>
              <p class="font-semibold text-white text-sm">Dive with caution</p>
              <p class="text-xs text-white/70 max-w-[220px]">Moderate swell or shifting visibility, check before diving.</p>
            </div>

            {{-- Red --}}
            <div class="flex flex-col items-center text-center gap-1.5">
              <span class="w-3 h-3 bg-rose-400 rounded-full shadow-sm"></span>
              <p class="font-semibold text-white text-sm">Not ideal</p>
              <p class="text-xs text-white/70 max-w-[220px]">Strong currents, poor visibility, or unsafe surf.</p>
            </div>

          </div>
        </div>
      </div>
    @endif

    {{-- 🧭 Local Intel --}}
    <div class="rounded-3xl p-6 sm:p-8 bg-white/10 backdrop-blur-xl border border-white/15 ring-1 ring-white/10 shadow-xl">
      <h3 class="text-white font-semibold text-lg mb-5 flex items-center gap-2">
        <img src="/icons/info.svg" class="w-4 h-4 invert opacity-80"> Local Intel
      </h3>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-slate-300">
        <div>
          <strong class="text-white">Best Wind:</strong>
          <p>{{ $diveSite->best_wind_dirs ?? 'N/A' }}</p>
        </div>

        <div>
          <strong class="text-white">Hazards:</strong>
          <p>{{ $diveSite->hazards ?? 'No major hazards recorded.' }}</p>
        </div>

        <div>
          <strong class="text-white">Entry Notes:</strong>
          <p>{{ $diveSite->entry_notes ?? 'Standard entry.' }}</p>
        </div>

        <div>
          <strong class="text-white">Parking:</strong>
          <p>{{ $diveSite->parking_notes ?? 'Limited nearby parking.' }}</p>
        </div>

        <div class="sm:col-span-2">
          <strong class="text-white">Marine Life:</strong>
          <p>{{ $diveSite->marine_life ?? 'Tropical reef species common.' }}</p>
        </div>
      </div>

      {{-- Optional: add small “Pro Tips” box --}}
      @if($diveSite->pro_tips)
        <div class="mt-6 rounded-xl bg-amber-500/10 border border-amber-400/30 p-4">
          <p class="text-amber-100 text-sm">
            💡 <strong>Pro Tip:</strong> {{ $diveSite->pro_tips }}
          </p>
        </div>
      @endif
    </div>

    {{-- 📖 About Section --}}
    <section class="max-w-4xl mx-auto px-6 sm:px-8 mb-16">
      <div class="rounded-3xl p-6 sm:p-8 bg-slate-900/40 backdrop-blur-xl border border-white/15 ring-1 ring-white/10 shadow-lg">
        <h3 class="text-white font-semibold text-lg mb-5 flex items-center gap-2">
          <img src="/icons/book-open.svg" class="w-4 h-4 invert opacity-80"> About This Site
        </h3>

        <div class="space-y-5 text-slate-300 leading-relaxed">
          <p>{{ $diveSite->description ?: 'No description provided yet.' }}</p>

          @if($diveSite->history)
            <div>
              <h4 class="text-white font-semibold text-sm uppercase tracking-wide mb-1">History</h4>
              <p>{{ $diveSite->history }}</p>
            </div>
          @endif

          @if($diveSite->what_to_see)
            <div>
              <h4 class="text-white font-semibold text-sm uppercase tracking-wide mb-1">What to See</h4>
              <p>{{ $diveSite->what_to_see }}</p>
            </div>
          @endif

          @if($diveSite->recommended_level)
            <div>
              <h4 class="text-white font-semibold text-sm uppercase tracking-wide mb-1">Recommended Level</h4>
              <p>{{ ucfirst($diveSite->recommended_level) }}</p>
            </div>
          @endif
        </div>
      </div>
    </section>
</section>

{{-- Mapbox Script --}}
@push('scripts')
<script src="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.js"></script>
<script>
  mapboxgl.accessToken = @json(config('services.mapbox.token'));
  const site = { lat: {{ $diveSite->lat }}, lng: {{ $diveSite->lng }}, status: '{{ $status }}' };

  function statusColor(s) {
    return {
      green: '#10B981', yellow: '#FACC15', red: '#EF4444', default: '#06B6D4'
    }[s] || '#06B6D4';
  }

  function buildMarkerEl(status) {
    const el = document.createElement('div');
    el.style.width = '16px';
    el.style.height = '16px';
    el.style.borderRadius = '9999px';
    const color = statusColor(status);
    el.style.background = color;
    el.style.boxShadow = `0 0 12px ${color}80`;
    return el;
  }

  function initMap(id, zoom) {
    const el = document.getElementById(id);
    if (!el) return;
    const map = new mapboxgl.Map({
      container: id,
      style: 'mapbox://styles/mapbox/streets-v11',
      center: [site.lng, site.lat],
      zoom, interactive: false
    });
    new mapboxgl.Marker({ element: buildMarkerEl(site.status) })
      .setLngLat([site.lng, site.lat])
      .addTo(map);
  }

  initMap('dive-site-map-mobile', 12);
  initMap('dive-site-map-desktop', 13);
</script>
@endpush
@endsection