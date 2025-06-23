@extends('layouts.vizzbud')

@section('title', 'Blog')
@section('meta_description', 'Read diving tips, site highlights, and the latest updates from the Vizzbud blog.')

@section('content')
<section class="max-w-4xl mx-auto px-4 sm:px-6 py-12">
    <h1 class="text-3xl sm:text-4xl font-bold text-white mb-8 text-center sm:text-left">📰 Vizzbud Blog</h1>

    @foreach ($posts as $post)
        <article class="bg-slate-800 rounded-xl p-6 shadow mb-6 hover:bg-slate-700 transition">
            <h2 class="text-2xl font-semibold text-cyan-400 mb-1">
                <a href="{{ route('blog.show', $post->slug) }}" class="hover:underline">
                    {{ $post->title }}
                </a>
            </h2>
            <p class="text-sm text-slate-400 mb-3">
                {{ $post->published_at->format('F j, Y') }}
            </p>
            <p class="text-slate-300">{{ $post->excerpt }}</p>
        </article>
    @endforeach

    <div class="mt-8">
        {{ $posts->links('pagination::tailwind') }}
    </div>
</section>
@endsection