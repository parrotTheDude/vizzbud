@extends('layouts.vizzbud')

@section('title', 'Edit Profile | Vizzbud')
@section('meta_description', 'Update your name and profile picture on Vizzbud.')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-slate-900 to-slate-800 text-slate-100 py-10 px-4 sm:px-6">
  <div class="max-w-3xl mx-auto space-y-10">

    {{-- 🧭 Header --}}
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-cyan-400">Edit Profile</h1>
      <a href="{{ route('profile.show') }}" class="text-sm text-slate-400 hover:text-cyan-400 transition">
        ← Back to Profile
      </a>
    </div>

    <form 
    id="profileForm"
    method="POST" 
    action="{{ route('profile.update') }}" 
    enctype="multipart/form-data"
    class="space-y-8 bg-slate-800/60 border border-slate-700 rounded-2xl p-6 shadow-md relative"
    >
    @csrf
    @method('PUT')

    {{-- 🔄 Overlay while uploading (hidden until submit) --}}
    <div id="uploadOverlay" 
        class="hidden absolute inset-0 bg-slate-900/80 backdrop-blur-sm flex flex-col items-center justify-center z-10 rounded-2xl">
        <svg class="animate-spin h-8 w-8 text-cyan-400 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
        <p class="text-sm text-cyan-300" id="overlayText">Uploading your profile...</p>
    </div>

    {{-- Profile Picture --}}
    <div>
        <label for="avatar" class="block text-sm font-medium text-slate-300 mb-2">Profile Picture</label>
        <div class="flex items-center gap-6">
        <img id="avatarPreview"
            src="{{ $user->avatar_url ?? asset('images/main/defaultProfile.webp') }}"
            alt="Profile Avatar"
            class="w-24 h-24 rounded-full object-cover border-2 border-cyan-400/30 shadow-sm">
        <input type="file" name="avatar" id="avatar" accept="image/*,.heic"
                class="text-sm text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-md
                        file:border-0 file:text-sm file:font-semibold
                        file:bg-cyan-600 file:text-white hover:file:bg-cyan-500 transition">
        </div>
        <p class="text-xs text-slate-400 mt-1">JPG, PNG, WEBP, or HEIC — max 4MB</p>
        @error('avatar')
        <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Name --}}
    <div>
        <label for="name" class="block text-sm font-medium text-slate-300 mb-1">Name</label>
        <input type="text" name="name" id="name"
            value="{{ old('name', $user->name) }}"
            class="w-full rounded-lg bg-white/10 border border-white/10 text-white px-3 py-2
                    focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
        @error('name')
        <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Save Button --}}
    <div class="pt-4 border-t border-slate-700 flex justify-end">
        <button 
        type="submit"
        id="saveButton"
        class="rounded-lg bg-cyan-600 hover:bg-cyan-500 px-6 py-3 text-white font-semibold shadow-md transition">
        Save Changes
        </button>
    </div>
    </form>
  </div>
</div>

<script type="module">
  import heicConvert from "https://cdn.jsdelivr.net/npm/heic-convert@2.1.0/dist/browser.js";

  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('profileForm');
    const overlay = document.getElementById('uploadOverlay');
    const overlayText = document.getElementById('overlayText');
    const fileInput = document.getElementById('avatar');
    const preview = document.getElementById('avatarPreview');
    const saveButton = document.getElementById('saveButton');
    let converting = false;

    fileInput.addEventListener('change', async (event) => {
      const file = event.target.files[0];
      if (!file) return;
      converting = true;
      saveButton.disabled = true;
      saveButton.classList.add('opacity-50', 'cursor-not-allowed');
      overlayText.textContent = 'Converting photo...';
      overlay.classList.remove('hidden');

      try {
        // 🔁 Detect and convert HEIC
        if (file.type === 'image/heic' || file.name.toLowerCase().endsWith('.heic')) {
          const arrayBuffer = await file.arrayBuffer();
          const outputBuffer = await heicConvert({ buffer: arrayBuffer, format: 'JPEG', quality: 0.9 });

          // Create new JPEG file
          const newFile = new File([outputBuffer], file.name.replace(/\.heic$/i, '.jpg'), { type: 'image/jpeg' });
          const dt = new DataTransfer();
          dt.items.add(newFile);
          event.target.files = dt.files;

          preview.src = URL.createObjectURL(newFile);
        } else {
          preview.src = URL.createObjectURL(file);
        }
      } catch (err) {
        console.error('HEIC conversion failed:', err);
        alert('We couldn’t process your HEIC photo. Please upload a JPEG or PNG.');
        event.target.value = '';
      } finally {
        converting = false;
        overlay.classList.add('hidden');
        saveButton.disabled = false;
        saveButton.classList.remove('opacity-50', 'cursor-not-allowed');
        overlayText.textContent = 'Uploading your profile...';
      }
    });

    form.addEventListener('submit', (e) => {
      if (converting) {
        e.preventDefault();
        alert('Please wait for the image conversion to finish.');
        return;
      }
      overlay.classList.remove('hidden'); // show overlay only when uploading
    });
  });
</script>
@endsection