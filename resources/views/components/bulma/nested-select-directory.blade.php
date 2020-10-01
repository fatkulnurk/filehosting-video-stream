<div>
    @foreach ($directories as $directory)

        <option {{ $isSelected($directory->id) ? 'selected="selected"' : '' }} value="{{ $directory->id }}">{{ $fromRoot }}/{{ $parrentDirectoryName }}/{{ $directory->name }}</option>

        @if (!blank($directory->childrenDirectory))
            <x-bulma.nested-select-directory :directories="$directory->childrenDirectory" :fromRoot="$fromRoot" :parrentDirectoryName="$directory->name"/>
        @endif
    @endforeach
</div>
