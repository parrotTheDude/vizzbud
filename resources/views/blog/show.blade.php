@extends('layouts.vizzbud')

@section('title', $post->title)

@section('content')
<section class="max-w-4xl mx-auto px-4 sm:px-6 py-12 text-white">
    <article class="space-y-6">
        <h1 class="text-4xl font-bold text-cyan-400">{{ $post->title }}</h1>
        <p class="text-slate-400 text-sm">
            {{ $post->published_at ? $post->published_at->format('F j, Y') : 'Unpublished' }}
        </p>

        @if ($post->featured_image)
            <img src="{{ asset('storage/' . $post->featured_image) }}" alt="{{ $post->featured_image_alt }}" class="rounded-xl shadow-lg mt-4 mb-6 max-h-[400px] w-full object-cover">
        @endif

        <div class="prose prose-invert max-w-none text-slate-100">
            {!! $post->html_content !!}
        </div>
    </article>
</section>
@endsection