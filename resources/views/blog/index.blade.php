@extends('layouts.vizzbud')

@section('title', 'Blog')
@section('meta_description', 'Read diving tips, site highlights, and the latest updates from the Vizzbud blog.')

@section('content')
<section class="max-w-6xl mx-auto px-4 sm:px-6 py-12">
    <h1 class="text-3xl sm:text-4xl font-bold text-white mb-10 text-center sm:text-left">📰 Vizzbud Blog</h1>

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