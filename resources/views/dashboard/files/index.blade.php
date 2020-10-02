<x-app-layout title="My Files">
    <x-slot name="header">
        <h2 class="title is-2">
            {{ __('My Files') }}
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

    <div class="columns mb-3">
        <div class="column is-3">
            <div class="box">
                <form action="{{ route('dashboard.directory.store', ['folder_id' => request()->has('folder_id') ? request()->folder_id : '']) }}" method="POST">
                    @csrf
                    <label class="label">Create Folder</label>
                    <div class="field has-addons is-fullwidth">
                        <div class="control">
                            <input value="{{ old('folder_name') }}" class="input @error('folder_name')is-danger @enderror" type="text" name="folder_name" placeholder="Create new folder">
                        </div>
                        <div class="control">
                            <button class="button is-primary">
                                Add
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <nav class="panel">
                <form action="{{ route('dashboard.directory.edit') }}" method="POST">
                    @csrf

                    <p class="panel-heading">
                        Folder
                    </p>
                    @if (request()->has('folder_id'))
                        <a href="{{ route('dashboard.files.index', ['folder_id' => $parrentDirectory]) }}" class="panel-block">
                            ...
                        </a>
                    @endif
                    @foreach ($directories as $directory)
                        <a href="{{ route('dashboard.files.index', ['folder_id' => $directory->id]) }}" class="panel-block">
                            <label class="checkbox">
                                <input type="checkbox" name="directories[]" value="{{ $directory->id }}">
                                {{ $directory->name }}
                            </label>
                        </a>
                    @endforeach

                    <div class="panel-block">
                        <div class="columns">
                            <div class="column is-6">
                                <input type="submit" class="button is-danger is-outlined is-fullwidth" value="Delete" name="delete">
                            </div>
                            <div class="column is-6">
                                <input type="submit" class="button is-primary is-outlined is-fullwidth" value="Rename" name="update">
                            </div>
                        </div>
                    </div>
                </form>
            </nav>
        </div>
        <div class="column">
            <div class="field">
                <div class="buttons has-addons">
                    <input name="button_delete" class="button is-danger" type="submit" value="Delete Files"/>
                    <input name="button_move" class="button is-warning" type="submit" value="Move Files"/>
                    <input name="button_embed_code" class="button is-primary" type="submit" value="Get Embed Code "/>
                </div>
            </div>
            <div class="field">
                <label class="label">All Files in Directory {{ $directoryName }}</label>
            </div>
            <div class="table-container">
                <table class="table is-fullwidth">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Size</th>
                        <th>View</th>
                        <th>Download</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach ($files as $file)

                        <tr>
                            <td>
                                <label class="checkbox is-primary">
                                    <input type="checkbox">
                                </label>
                            </td>
                            <td><a href="{{ route('file-show', $file->code) }}">{{ $file->client_original_name }}</a></td>
                            <td>{{ $file->size_format }}</td>
                            <td>0</td>
                            <td>0</td>
                            <td>
                                <livewire:dashboard.file-button :file="$file"/>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            {{ $files->withQueryString()->links() }}

        </div>
    </div>
</x-app-layout>
