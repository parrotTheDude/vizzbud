@extends('layouts.vizzbud')

@section('title', 'Manage Blog')

@section('content')
<section class="max-w-6xl mx-auto px-4 sm:px-6 py-12">
    <h1 class="text-3xl font-bold text-white mb-6">📝 Manage Blog Posts</h1>

    <a href="{{ route('admin.blog.create') }}" class="bg-cyan-500 hover:bg-cyan-600 text-white px-4 py-2 rounded font-semibold mb-6 inline-block">
        ➕ New Post
    </a>

    @if(session('success'))
        <div class="bg-green-600 text-white px-4 py-2 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($posts as $post)
            <div class="bg-slate-800 rounded-xl shadow overflow-hidden flex flex-col h-full">
                @if($post->featured_image)
                    <img src="{{ asset('storage/' . $post->featured_image) }}"
                         alt="{{ $post->featured_image_alt ?? 'Featured image' }}"
                         class="w-full h-48 object-cover">
                @endif

                <div class="p-4 flex flex-col flex-grow">
                    <h2 class="text-lg font-semibold text-cyan-400 mb-1">
                        <a href="{{ route('blog.show', $post->slug) }}" target="_blank">{{ $post->title }}</a>
                    </h2>

                    <p class="text-slate-300 text-sm mb-2">{{ $post->excerpt }}</p>

                    <p class="text-slate-500 text-xs mt-auto mb-4">
                        Published: {{ $post->published_at ? $post->published_at->format('F j, Y') : 'Draft' }}
                    </p>
                </div>

                <div class="flex w-full border-t border-slate-700">
                    <a href="{{ route('admin.blog.edit', $post) }}"
                    class="flex-1 text-center text-sm font-semibold bg-yellow-500 hover:bg-yellow-600 text-white py-3 max-h-[60px]">
                        ✏️ Edit
                    </a>
                    <form action="{{ route('admin.blog.togglePublish', $post) }}" method="POST" class="flex-1">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="w-full text-sm font-semibold {{ $post->published ? 'bg-red-600 hover:bg-gray-700' : 'bg-green-600 hover:bg-green-700' }} text-white py-3 max-h-[60px] text-center">
                            {{ $post->published ? '📥 Unpublish' : '✅ Publish' }}
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $posts->links() }}
    </div>
</section>
@endsection