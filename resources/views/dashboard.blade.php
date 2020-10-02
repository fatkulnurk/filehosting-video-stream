<x-app-layout>
    <x-slot name="header">
        <h2 class="title is-2">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>
    <div class="section">
        <div class="columns">
            <div class="column is-4">
                <div class="box">
                    <h3 class="title is-3">{{ $directory }} Directory</h3>
                    <p class="subtitle is-5">Total Directory Created</p>
                </div>
            </div>
            <div class="column is-4">
                <div class="box">
                    <h3 class="title is-3">{{ $file }} File</h3>
                    <p class="subtitle is-5">Total Video/Files Uploaded</p>
                </div>
            </div>
            <div class="column is-4">
                <div class="box">
                    <h3 class="title is-3">{{ $sizeFormat }}</h3>
                    <p class="subtitle is-5">Total Space Used</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
