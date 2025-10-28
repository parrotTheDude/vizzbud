@extends('layouts.vizzbud')

@section('title', 'Blog | Vizzbud')
@section('meta_description', 'Read diving tips, site highlights, and the latest updates from the Vizzbud blog — your guide to smarter, safer dives.')

@push('head')
  {{-- Canonical --}}
  <link rel="canonical" href="{{ url('/blog') }}">

  {{-- Open Graph / Twitter --}}
  <meta property="og:type" content="website">
  <meta property="og:title" content="Vizzbud Blog | Dive Tips, Guides & Updates">
  <meta property="og:description" content="Explore dive stories, safety tips, and platform updates from the Vizzbud blog.">
  <meta property="og:image" content="{{ asset('images/divesites/default.webp') }}">
  <meta property="og:url" content="{{ url('/blog') }}">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Vizzbud Blog | Dive Tips, Guides & Updates">
  <meta name="twitter:description" content="Read the latest posts from the Vizzbud team — diving guides, conditions insights, and platform updates.">
  <meta name="twitter:image" content="{{ asset('images/divesites/default.webp') }}">

  {{-- Structured Data: Blog Index --}}
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Blog",
    "name": "Vizzbud Blog",
    "description": "Official Vizzbud blog featuring dive tips, site highlights, and platform updates.",
    "url": "https://vizzbud.com/blog",
    "publisher": {
      "@type": "Organization",
      "name": "Vizzbud",
      "logo": {
        "@type": "ImageObject",
        "url": "https://vizzbud.com/android-chrome-512x512.png"
      }
    }
  }
  </script>
@endpush

@section('content')
<section class="max-w-6xl mx-auto px-4 sm:px-6 py-12">
    <h1 class="text-3xl sm:text-4xl font-bold text-white mb-10 text-center sm:text-left">Vizzbud Blog</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($posts as $post)
            <article class="bg-slate-800 rounded-xl shadow overflow-hidden flex flex-col h-full hover:bg-slate-700 transition">
                @if($post->featured_image)
                    <a href="{{ route('blog.show', $post->slug) }}">
                        <img src="{{ asset('storage/' . $post->featured_image) }}"
                             alt="{{ $post->featured_image_alt ?? 'Featured image' }}"
                             class="w-full h-48 object-cover">
                    </a>
                @endif

                <div class="p-4 flex flex-col flex-grow">
                    <h2 class="text-lg font-semibold text-cyan-400 mb-1">
                        <a href="{{ route('blog.show', $post->slug) }}" class="hover:underline">
                            {{ $post->title }}
                        </a>
                    </h2>

                    <p class="text-slate-300 text-sm mb-2">
                        {{ $post->excerpt }}
                    </p>

                    <p class="text-slate-500 text-xs mt-auto">
                        Published: {{ $post->published_at->format('F j, Y') }}
                    </p>
                </div>
            </article>
        @endforeach
    </div>

    <div class="mt-10">
        {{ $posts->links('pagination::tailwind') }}
    </div>
</section>
@endsection