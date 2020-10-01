<form wire:submit.prevent="save" enctype="multipart/form-data" >
    <input type="file" wire:model="photo">

    @error('photo') <span class="error">{{ $message }}</span> @enderror

    <button type="submit">Save Photo</button>
</form>
