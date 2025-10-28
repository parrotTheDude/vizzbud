@extends('layouts.vizzbud')

@section('title', "{$post->title} | Vizzbud Blog")
@section('meta_description', Str::limit(strip_tags($post->excerpt ?? $post->content), 160))

@push('head')
  {{-- Canonical --}}
  <link rel="canonical" href="{{ route('blog.show', $post->slug) }}">

  {{-- Open Graph / Twitter --}}
  <meta property="og:type" content="article">
  <meta property="og:title" content="{{ $post->title }}">
  <meta property="og:description" content="{{ Str::limit(strip_tags($post->excerpt ?? $post->content), 200) }}">
  <meta property="og:image" content="{{ $post->featured_image ? asset($post->featured_image) : asset('images/divesites/default.webp') }}">
  <meta property="og:url" content="{{ route('blog.show', $post->slug) }}">
  <meta property="article:published_time" content="{{ $post->created_at->toIso8601String() }}">
  <meta property="article:modified_time" content="{{ $post->updated_at->toIso8601String() }}">
  <meta property="article:author" content="{{ $post->author->name ?? 'Vizzbud Team' }}">
  <meta property="og:site_name" content="Vizzbud">

  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="{{ $post->title }}">
  <meta name="twitter:description" content="{{ Str::limit(strip_tags($post->excerpt ?? $post->content), 200) }}">
  <meta name="twitter:image" content="{{ $post->featured_image ? asset($post->featured_image) : asset('images/divesites/default.webp') }}">

  {{-- Structured Data: BlogPosting --}}
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "BlogPosting",
    "headline": "{{ $post->title }}",
    "description": "{{ Str::limit(strip_tags($post->excerpt ?? $post->content), 200) }}",
    "image": "{{ $post->featured_image ? asset($post->featured_image) : asset('images/divesites/default.webp') }}",
    "author": {
      "@type": "Person",
      "name": "{{ $post->author->name ?? 'Vizzbud Team' }}"
    },
    "publisher": {
      "@type": "Organization",
      "name": "Vizzbud",
      "logo": {
        "@type": "ImageObject",
        "url": "https://vizzbud.com/android-chrome-512x512.png"
      }
    },
    "datePublished": "{{ $post->created_at->toIso8601String() }}",
    "dateModified": "{{ $post->updated_at->toIso8601String() }}",
    "mainEntityOfPage": {
      "@type": "WebPage",
      "@id": "{{ route('blog.show', $post->slug) }}"
    }
  }
  </script>
@endpush

@section('content')
<section class="max-w-4xl mx-auto px-4 sm:px-6 py-12 text-white">
    <article class="space-y-6">
        <h1 class="text-4xl font-bold text-cyan-400">{{ $post->title }}</h1>
        <p class="text-slate-400 text-sm">{{ $post->published_at ? $post->published_at->format('F j, Y') : 'Unpublished' }}</p>

        @if ($post->featured_image)
            <img src="{{ asset('storage/' . $post->featured_image) }}" alt="{{ $post->featured_image_alt }}" class="rounded-xl shadow-lg mt-4 mb-6 max-h-[400px] w-full object-cover">
        @endif

        {{-- Share buttons --}}
        <div class="flex gap-4 mt-6 items-center">
            @php $url = urlencode(request()->fullUrl()); @endphp

            <a href="https://www.facebook.com/sharer/sharer.php?u={{ $url }}" target="_blank" title="Share on Facebook">
                @include('components.icon', ['name' => 'facebook'])
            </a>

            <a href="https://twitter.com/intent/tweet?url={{ $url }}" target="_blank" title="Share on Twitter">
                @include('components.icon', ['name' => 'twitter'])
            </a>

            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $url }}" target="_blank" title="Share on LinkedIn">
                @include('components.icon', ['name' => 'linkedin'])
            </a>

            <button id="copyLinkButton" type="button" title="Copy link" class="hover:scale-110 transition-transform">
                @include('components.icon', ['name' => 'direct', 'class' => 'w-5 h-5'])
            </button>
        </div>

        <div class="prose prose-invert max-w-none text-slate-100">
            {!! $post->html_content !!}
        </div>
    </article>
</section>

@push('scripts')
<script>
function sharePost() {
    if (navigator.share) {
        navigator.share({
            title: @json($post->title),
            url: @json(request()->fullUrl())
        }).catch(console.error);
    } else {
        alert("Your browser doesn't support native sharing. Try one of the icons below!");
    }
}

    document.getElementById('copyLinkButton').addEventListener('click', function () {
        const url = window.location.href;
        navigator.clipboard.writeText(url).then(() => {
            alert('Link copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy: ', err);
        });
    });
</script>
@endpush
@endsection