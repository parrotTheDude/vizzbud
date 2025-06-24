@extends('layouts.vizzbud')

@section('title', 'Manage Blog')

@section('content')
<section class="max-w-5xl mx-auto px-4 sm:px-6 py-12">
    <h1 class="text-3xl font-bold text-white mb-6">📝 Manage Blog Posts</h1>

    <a href="{{ route('admin.blog.create') }}" class="bg-cyan-500 hover:bg-cyan-600 text-white px-4 py-2 rounded font-semibold mb-6 inline-block">
        ➕ New Post
    </a>

    @if(session('success'))
        <div class="bg-green-600 text-white px-4 py-2 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="space-y-6">
        @foreach ($posts as $post)
            <div class="bg-slate-800 rounded-xl p-6 shadow">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-xl font-semibold text-cyan-400">
                        <a href="{{ route('blog.show', $post->slug) }}" target="_blank">{{ $post->title }}</a>
                    </h2>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.blog.edit', $post) }}" class="text-sm text-yellow-400 hover:underline">Edit</a>
                        <form action="{{ route('admin.blog.destroy', $post) }}" method="POST" onsubmit="return confirm('Delete this post?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-500 hover:underline">Delete</button>
                        </form>
                    </div>
                </div>
                <p class="text-slate-300 text-sm">{{ $post->excerpt }}</p>
                <p class="text-slate-500 text-xs mt-1">Published: {{ $post->published_at ? $post->published_at->format('F j, Y') : 'Draft' }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $posts->links() }}
    </div>
</section>
@endsection