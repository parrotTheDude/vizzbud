@extends('layouts.vizzbud')

@section('title', 'Blog')

@section('content')
    <h1 class="text-2xl font-bold mb-6">Vizzbud Blog</h1>

    @foreach ($posts as $post)
        <article class="mb-8 border-b pb-4">
            <h2 class="text-xl font-semibold">
                <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
            </h2>
            <p class="text-sm text-gray-600">{{ $post->published_at->format('F j, Y') }}</p>
            <p class="mt-2">{{ $post->excerpt }}</p>
        </article>
    @endforeach

    {{ $posts->links() }}
@endsection