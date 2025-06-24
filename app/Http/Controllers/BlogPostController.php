<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;
use League\CommonMark\MarkdownConverter;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class BlogPostController extends Controller
{
    public function index()
    {
        $posts = BlogPost::where('published', true)
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->paginate(5);

        return view('blog.index', compact('posts'));
    }

    public function show($slug)
    {
        $post = BlogPost::where('slug', $slug)->firstOrFail();

        $converter = app(MarkdownConverter::class);
        $post->content = $converter->convert($post->content)->getContent();

        return view('blog.show', compact('post'));
    }

    public function adminIndex()
    {
        $posts = BlogPost::latest()->paginate(10);
        return view('admin.blog.index', compact('posts'));
    }

    public function create()
    {
        $post = new BlogPost();
        return view('admin.blog.create', compact('post'));
    }

    public function store(Request $request, ImageManager $image)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:blog_posts',
            'excerpt' => 'required|string|max:300',
            'content' => 'required|string',
            'published_at' => 'nullable|date',
            'featured_image' => 'nullable|image|max:2048',
            'featured_image_alt' => 'required|string|max:255',
        ]);

        if ($request->hasFile('featured_image')) {
            $resized = $image->read($request->file('featured_image')->getPathname())
                ->scale(width: 1200)
                ->toWebp(85);

            $filename = 'blog/' . uniqid() . '.webp';
            Storage::disk('public')->put($filename, $resized);
            $validated['featured_image'] = $filename;
        }

        $validated['published'] = !empty($validated['published_at']);

        BlogPost::create($validated);

        return redirect()->route('admin.blog.index')->with('success', 'Post created.');
    }

    public function edit(BlogPost $post)
    {
        return view('admin.blog.edit', compact('post'));
    }

    public function update(Request $request, BlogPost $post, ImageManager $image)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:blog_posts,slug,' . $post->id,
            'excerpt' => 'required|string|max:300',
            'content' => 'required|string',
            'published_at' => 'nullable|date',
            'featured_image' => 'nullable|image|max:2048',
            'featured_image_alt' => 'required|string|max:255',
        ]);

        if ($request->hasFile('featured_image')) {
            $resized = $image->read($request->file('featured_image')->getPathname())
                ->scale(width: 1200)
                ->toWebp(85);

            $filename = 'blog/' . uniqid() . '.webp';
            Storage::disk('public')->put($filename, $resized);
            $validated['featured_image'] = $filename;
        }

        $validated['published'] = !empty($validated['published_at']);

        $post->update($validated);

        return redirect()->route('admin.blog.index')->with('success', 'Post updated.');
    }

    public function destroy(BlogPost $post)
    {
        $post->delete();
        return back()->with('success', 'Post deleted.');
    }

    public function uploadImage(Request $request, ImageManager $image)
    {
        try {
            $request->validate([
                'image' => 'required|image|max:2048',
            ]);

            $resized = $image->read($request->file('image')->getPathname())
                ->resize(1200, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })
                ->toWebp(80);

            $filename = 'blog/' . uniqid() . '.webp';
            Storage::disk('public')->put($filename, $resized);

            return response()->json(['location' => Storage::url($filename)]);
        } catch (\Throwable $e) {
            \Log::error('TinyMCE Image Upload Failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Upload failed.'], 500);
        }
    }
}