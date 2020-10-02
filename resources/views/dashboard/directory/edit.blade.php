<x-app-layout title="Manage Directory">
    <x-slot name="header">
        <h2 class="title is-2">
            @if (request()->has('delete'))
                {{ __('Delete Directory') }}
            @elseif(request()->has('update'))
                {{ __('Update Directory') }}
            @endif
        </h2>
    </x-slot>

    <div class="columns">
        <div class="column is-6 is-offset-3">
            <div class="box">

                @if (request()->has('delete'))
                    <form action="{{ route('dashboard.directory.delete') }}" method="POST">
                        @csrf
                        @method('DELETE')

                        @foreach ($directories as $directory)
                            <div class="field">
                                <label class="label">{{ $directory->name }}</label>
                                <div class="control">
                                    <input class="input" name="directories[]" type="hidden" value="{{ $directory->id }}">
                                </div>
                            </div>
                        @endforeach

                        <div class="notification is-warning">
                            All directory, Sub directory and files in directory will be delete.
                        </div>

                        <button class="button is-fullwidth is-primary">Delete Directory & Data</button>
                    </form>
                @elseif(request()->has('update'))
                    <form action="{{ route('dashboard.directory.update') }}" method="POST">
                        @csrf

                        @foreach ($directories as $directory)
                            <div class="field">
                                <label class="label">{{ $directory->name }}</label>
                                <div class="control">
{{--                                    <input class="input" name="directories[][{{ $directory->id }}][id]" type="hidden" value="{{ $directory->id }}">--}}
                                    <input class="input" name="directories[][{{ $directory->id }}]" type="text" value="{{ $directory->name }}">
                                </div>
                            </div>
                        @endforeach

                        <button class="button is-fullwidth is-primary">Update</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

</x-app-layout>
