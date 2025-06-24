@php $editing = isset($post); @endphp

<div>
    <label class="block text-slate-300 mb-1">Title</label>
    <input name="title" value="{{ old('title', $post->title ?? '') }}" class="w-full p-2 rounded bg-slate-700 text-white" required />
</div>

<div>
    <label class="block text-slate-300 mb-1">Slug</label>
    <input name="slug" value="{{ old('slug', $post->slug ?? '') }}" class="w-full p-2 rounded bg-slate-700 text-white" required />
</div>

<div>
    <label class="block text-slate-300 mb-1">Excerpt</label>
    <textarea name="excerpt" rows="2" class="w-full p-2 rounded bg-slate-700 text-white" required>{{ old('excerpt', $post->excerpt ?? '') }}</textarea>
</div>

<div>
    <label class="block text-slate-300 mb-1">Body</label>
    <textarea id="content" name="content" class="w-full p-2 rounded bg-white text-black" rows="12" required>{{ old('content', $post->content ?? '') }}</textarea>
</div>

<div>
    <label class="block text-slate-300 mb-1">Published At (optional)</label>
    <input type="datetime-local" name="published_at" value="{{ old('published_at', isset($post) && $post->published_at ? $post->published_at->format('Y-m-d\TH:i') : '') }}" class="w-full p-2 rounded bg-slate-700 text-white" />
</div>

<div class="mt-4">
    <label class="block text-slate-300 mb-1">Featured Image</label>
    <input type="file" name="featured_image" class="block text-white" accept="image/*" />
    @if (!empty($post->featured_image))
        <p class="text-sm text-slate-400 mt-1">
            Current: <a href="{{ asset('storage/' . $post->featured_image) }}" target="_blank" class="underline text-cyan-400">View</a>
        </p>
    @endif
</div>

<div>
    <label class="block text-slate-300 mb-1">Image Alt Text</label>
    <input name="featured_image_alt" value="{{ old('featured_image_alt', $post->featured_image_alt ?? '') }}" class="w-full p-2 rounded bg-slate-700 text-white" required />
</div>