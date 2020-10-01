<x-app-layout title="Upload File">
    <x-slot name="header">
        <h2 class="title is-2">
            {{ __('Upload File') }}
        </h2>
    </x-slot>

    <div class="field">
        @if ($errors->any())
            <div class="notification is-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <div class="box my-3 has-text-centered">
        <form method="POST" enctype="multipart/form-data" action="{{ route('dashboard.files.store') }}">
            @csrf
            <div class="field">
                <label for="" class="label">Select Video</label>
                <input name="video" type="file" class="control" style="border: dashed teal 2px; padding: 30px 30px 30px 30px !important; border-radius: 1%">
            </div>
            <div class="field">
                <div class="control">
                    <label for="" class="label">Select Folder</label>
                    <div class="select">
                        <select name="directory_id">
                            <option value="">/</option>
                            @foreach ($directories as $directory)
                                <option value="{{ $directory->id }}">/{{ $directory->name }}</option>
                                @if (!blank($directory->childrenDirectory))
                                    <x-bulma.nested-select-directory :directories="$directory->childrenDirectory" :fromRoot="$directory->name" :parrentDirectoryName="$directory->name"/>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="field">
                <button class="button is-primary">Upload Video</button>
            </div>
        </form>
    </div>

</x-app-layout>
