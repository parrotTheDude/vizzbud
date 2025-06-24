@extends('layouts.vizzbud')

@section('title', 'Edit Post')

@section('content')
<section class="max-w-4xl mx-auto px-4 sm:px-6 py-12">
    <h1 class="text-3xl font-bold text-white mb-6">✏️ Edit Blog Post</h1>

    @if ($errors->any())
        <div class="bg-red-600 text-white px-4 py-2 rounded mb-6">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-slate-800 p-6 rounded-xl shadow text-white">
        <form action="{{ route('admin.blog.update', $post->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            @include('admin.blog._form', ['post' => $post])

            <div class="pt-4">
                <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded font-semibold">
                    💾 Update Post
                </button>
            </div>
        </form>
    </div>
</section>
@push('scripts')
<script src="https://cdn.tiny.cloud/1/{{ config('services.tinymce.key') }}/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#content',
    plugins: 'image code',
    toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | code | image',
    images_upload_url: '{{ route('admin.blog.upload') }}',
    automatic_uploads: true,
    images_upload_handler: function (blobInfo, success, failure) {
        const formData = new FormData();
        formData.append('image', blobInfo.blob(), blobInfo.filename());

        fetch('{{ route('admin.blog.upload') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data && data.location) {
                success(data.location);
            } else {
                failure && failure('Invalid response');
            }
        })
        .catch(error => {
            console.error('Image upload failed:', error);
            failure && failure(error.message);
        });
    }
});
</script>
@endpush
@endsection