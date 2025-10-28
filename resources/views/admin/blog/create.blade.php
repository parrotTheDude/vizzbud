@extends('layouts.vizzbud')

@section('title', 'New Blog Post | Admin | Vizzbud')
@section('meta_description', 'Create and publish a new blog post in the Vizzbud admin dashboard. Add titles, content, tags, and images.')

@push('head')
  {{-- 🚫 Prevent indexing (private admin area) --}}
  <meta name="robots" content="noindex, nofollow">

  {{-- Canonical (for internal reference) --}}
  <link rel="canonical" href="{{ route('admin.blog.create') }}">

  {{-- Theme and UI --}}
  <meta name="theme-color" content="#0f172a">
  <meta name="application-name" content="Vizzbud Admin">
  <meta name="color-scheme" content="dark">

  {{-- Optional structured data for internal use --}}
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "WebApplication",
    "name": "Vizzbud Admin — New Blog Post",
    "url": "{{ route('admin.blog.create') }}",
    "description": "Administrative dashboard page for creating and publishing new blog posts on Vizzbud.",
    "creator": {
      "@type": "Organization",
      "name": "Vizzbud"
    }
  }
  </script>
@endpush

@section('content')
<section class="max-w-4xl mx-auto px-4 sm:px-6 py-12">
    <h1 class="text-3xl font-bold text-white mb-6">Create New Post</h1>

    @if ($errors->any())
        <div class="bg-red-600 text-white px-4 py-2 rounded mb-4">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-slate-800 p-6 rounded-xl shadow text-white">
        <form action="{{ route('admin.blog.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            @include('admin.blog._form')

            <div class="pt-4">
                <button type="submit" class="bg-cyan-500 hover:bg-cyan-600 text-white px-6 py-2 rounded font-semibold">
                    ➕ Publish Post
                </button>
            </div>
        </form>
    </div>
</section>

@push('scripts')
<script src="https://cdn.tiny.cloud/1/{{ config('services.tinymce.key') }}/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Auto-generate slug from title
    const titleInput = document.querySelector('#title');
    const slugInput = document.querySelector('#slug');
    const slugDisplay = document.querySelector('#slugDisplay');

    if (titleInput && slugInput && slugDisplay) {
        titleInput.addEventListener('input', function () {
            const slug = this.value
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')  // replace non-alphanumerics with dashes
                .replace(/^-+|-+$/g, '');     // trim dashes

            slugInput.value = slug;
            slugDisplay.textContent = slug;
        });
    }

    // TinyMCE setup
    tinymce.init({
        selector: '#content',
        plugins: 'image code',
        toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | code | image',
        images_upload_url: '{{ route("admin.blog.upload") }}',
        automatic_uploads: true,
        images_upload_handler: function (blobInfo, success, failure) {
            let formData = new FormData();
            formData.append('image', blobInfo.blob(), blobInfo.filename());

            fetch('{{ route("admin.blog.upload") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.location) {
                    success(data.location);
                } else {
                    failure('Invalid response structure');
                }
            })
            .catch(() => failure('Image upload failed'));
        }
    });
});
</script>
@endpush
@endsection