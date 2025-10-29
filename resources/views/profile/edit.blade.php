@extends('layouts.vizzbud')

@section('title', 'Edit Profile | Vizzbud')
@section('meta_description', 'Update your name, profile picture, and certification on Vizzbud.')

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

      {{-- 🔄 Upload Overlay --}}
      <div id="uploadOverlay" 
           class="hidden absolute inset-0 bg-slate-900/80 backdrop-blur-sm flex flex-col items-center justify-center z-10 rounded-2xl">
        <svg class="animate-spin h-8 w-8 text-cyan-400 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
        <p class="text-sm text-cyan-300" id="overlayText">Uploading your profile...</p>
      </div>

      {{-- Profile Picture --}}
      <div x-data="{ hasPhoto: {{ $user->profile->avatar_url ? 'true' : 'false' }}, changing: false }">
        <label class="block text-sm font-medium text-slate-300 mb-2">Profile Picture</label>
        <template x-if="hasPhoto && !changing">
          <div class="flex items-center gap-6">
            <img src="{{ $user->profile->avatar_url ?? asset('images/main/defaultProfile.webp') }}"
                 alt="Profile Avatar"
                 class="w-24 h-24 rounded-full object-cover border-2 border-cyan-400/30 shadow-sm">
            <div class="flex flex-col gap-2">
              <button type="button" @click="changing = true"
                      class="text-sm px-4 py-2 rounded-md bg-cyan-600 text-white hover:bg-cyan-500 transition">
                Change
              </button>
              <button type="submit" formaction="{{ route('profile.removeAvatar') }}" formmethod="POST" 
                      class="text-sm px-4 py-2 rounded-md bg-red-600 text-white hover:bg-red-500 transition">
                @csrf
                Remove
              </button>
            </div>
          </div>
        </template>

        <template x-if="!hasPhoto || changing">
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
        </template>
        <p class="text-xs text-slate-400 mt-1">JPG, PNG, WEBP, or HEIC — max 4MB</p>
        @error('avatar')
          <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
        @enderror
      </div>

      {{-- 🧾 Name --}}
      <div>
        <label for="name" class="block text-sm font-medium text-slate-300 mb-1">Display Name</label>
        <input type="text" name="name" id="name"
               value="{{ old('name', $user->name) }}"
               class="w-full rounded-lg bg-white/10 border border-white/10 text-white px-3 py-2
                      focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition"
               maxlength="50">
        <p class="text-xs text-slate-500 mt-1">This will appear on your public profile.</p>
        @error('name')
          <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
        @enderror
      </div>

      {{-- 🥽 Certification --}}
      <div>
        <label for="dive_level_id" class="block text-sm font-medium text-slate-300 mb-1">
          Certification Level
        </label>
        <select name="dive_level_id" id="dive_level_id"
                class="w-full rounded-lg bg-white/10 border border-white/10 text-white px-3 py-2
                      focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">
          <option value="">Select your level</option>
          @foreach($diveLevels as $level)
            <option value="{{ $level->id }}" 
                    @selected(old('dive_level_id', $user->profile->dive_level_id ?? null) == $level->id)>
              {{ $level->name }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- ✍️ Bio --}}
      <div>
        <label for="bio" class="block text-sm font-medium text-slate-300 mb-1">Short Bio</label>
        <textarea name="bio" id="bio" rows="3" maxlength="160"
                  placeholder="Tell others a bit about your diving experience..."
                  class="w-full rounded-lg bg-white/10 border border-white/10 text-white px-3 py-2
                         focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 transition">{{ old('bio', $user->profile->bio ?? '') }}</textarea>
        <p class="text-xs text-slate-500 mt-1">Max 160 characters.</p>
      </div>

      {{-- 💾 Save --}}
      <div class="pt-4 border-t border-slate-700 flex justify-end">
        <button type="submit" id="saveButton"
                class="rounded-lg bg-cyan-600 hover:bg-cyan-500 px-6 py-3 text-white font-semibold shadow-md transition">
          Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

{{-- 🖼 Live Avatar Preview --}}
<script>
document.getElementById('avatar')?.addEventListener('change', (event) => {
  const [file] = event.target.files;
  if (file) {
    const preview = document.getElementById('avatarPreview');
    preview.src = URL.createObjectURL(file);
  }
});
</script>
@endsection