@extends('layouts.vizzbud')

@section('title', $post->title)

@section('content')
    <article class="prose max-w-none">
        <h1>{{ $post->title }}</h1>
        <p class="text-sm text-gray-500 mb-4">{{ $post->published_at->format('F j, Y') }}</p>
        @if ($post->featured_image)
            <img src="{{ asset('storage/' . $post->featured_image) }}" alt="{{ $post->title }}" class="mb-4">
        @endif
        {!! nl2br(e($post->content)) !!}
    </article>
@endsection