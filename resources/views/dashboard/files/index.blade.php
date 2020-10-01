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
                            <input type="submit" class="button is-primary is-outlined is-fullwidth" value="Rename" name="rename">
                        </div>
                    </div>
                </div>
            </nav>
        </div>
        <div class="column">

            <div class="field">
                <label class="label">Search File</label>
                <p class="control has-icons-left">
                    <input class="input" type="text" placeholder="Search">
                    <span class="icon is-left">
                        <i class="fas fa-search" aria-hidden="true"></i>
                    </span>
                </p>
            </div>

            <div class="columns">
                <div class="column">
                    <div class="field has-addons">
                        <p class="control">
                            <button class="button">
      <span class="icon is-small">
        <i class="fas fa-align-left"></i>
      </span>
                                <span>Delete</span>
                            </button>
                        </p>
                        <p class="control">
                            <button class="button">
      <span class="icon is-small">
        <i class="fas fa-align-center"></i>
      </span>
                                <span>Move</span>
                            </button>
                        </p>
                        <p class="control">
                            <button class="button">
      <span class="icon is-small">
        <i class="fas fa-align-center"></i>
      </span>
                                <span>Set Category</span>
                            </button>
                        </p>
                        <p class="control">
                            <button class="button">
      <span class="icon is-small">
        <i class="fas fa-align-right"></i>
      </span>
                                <span>Get Embed Code</span>
                            </button>
                        </p>
                    </div>
                </div>
                <div class="column is-3">
                    <a href="{{ route('dashboard.files.create') }}" class="button is-primary is-fullwidth">Upload Video</a>
                </div>
            </div>
            <hr>
            <div class="field">
                <label class="label">My Files</label>
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
                            <td>{{ $file->client_original_name }}</td>
                            <td>{{ $file->size_format }}</td>
                            <td>0</td>
                            <td>0</td>
                            <td>
                                <div class="dropdown">
                                    <div class="dropdown-trigger">
                                        <button class="button" aria-haspopup="true" aria-controls="dropdown-menu">
                                            {{--                                            <span>Dropdown button</span>--}}
                                            <span class="icon is-small">
        <i class="fas fa-angle-down" aria-hidden="true"></i>
      </span>
                                        </button>
                                    </div>
                                    <div class="dropdown-menu" id="dropdown-menu" role="menu">
                                        <div class="dropdown-content">
                                            <a href="#" class="dropdown-item">
                                                Edit
                                            </a>
                                            <a class="dropdown-item">
                                                Delete
                                            </a>
                                            <a href="#" class="dropdown-item is-active">
                                                Copy Embed
                                            </a>
                                        </div>
                                    </div>
                                </div>
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
