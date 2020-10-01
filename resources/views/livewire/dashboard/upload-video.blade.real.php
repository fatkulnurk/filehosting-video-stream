<div
    x-data="{ isUploading: false, progress: 0 }"
    x-on:livewire-upload-start="isUploading = true"
    x-on:livewire-upload-finish="isUploading = false"
    x-on:livewire-upload-error="isUploading = false"
    x-on:livewire-upload-progress="progress = $event.detail.progress"
    class="box">

    <form wire:submit.prevent="save">

        @error('video')
        <div class="notification is-danger">{{ $message }}</div>
        @enderror

        <div class="field">
            <input type="file" wire:model="video" accept="video/mp4, video/mpeg, video/*">

            <button type="submit">Upload</button>
        </div>

        <div x-show="isUploading">
            <div class="field">
                <label class="label">Loading</label>
                <progress max="100" class="progress is-primary" x-bind:value="progress"></progress>
            </div>
        </div>
        @if ($video)
            Vidio Berhasil di upload <br>
            {{ $video->temporaryUrl() }}
        @endif
    </form>
</div>

<script>
    let file = document.querySelector('input[type="file"]').files[0]

    // Upload a file:
    @this.upload('video', file, (uploadedFilename) => {
        // Success callback.
        window.alert('sukses')
        alert('sukses kok')
    }, () => {
        // Error callback.
        window.alert('error')
        alert('error')
    }, (event) => {
        // Progress callback.
        // event.detail.progress contains a number between 1 and 100 as the upload progresses.

        window.alert('event')
        alert('event')
    })

    // Upload multiple files:
    @this.uploadMultiple('photos', [file], successCallback, errorCallback, progressCallback)

    // Remove single file from multiple uploaded files
    @this.removeUpload('photos', uploadedFilename, successCallback)
</script>
