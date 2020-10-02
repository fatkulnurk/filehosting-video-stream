<x-guest-layout title="Download File">
    <div class="mb-3 mt-5">
        <div class="columns">
            <div class="column is-6 is-offset-3">
                <div class="box">
                    <iframe src="{{ route('embed-show', $file->code) }}" width="100%" height="310px"></iframe>
                </div>
            </div>
        </div>
    </div>
    <div class="hero is-primary is-vcentered has-text-centered">
        <div class="hero-body">
            <div class="container">
                <h1 class="title">
                    {{ $file->client_original_name }}
                </h1>
                <h2 class="subtitle is-capitalized">
                    Size: {{ $file->size_format }} <span class="has-text-weight-bold">.</span> Mime Type: {{ $file->mime_type }}
                </h2>
                <form action="{{ route('file-download', $file->code) }}" method="POST">
                    @csrf
                    <input type="hidden" name="code" value="{{ $file->code }}">
                    <input type="hidden" name="hash" value="{{ $file->path_hash }}">
                    <input type="hidden" name="key" value="{{ $file->expired_time }}">
                    <div class="buttons is-centered">
                        <button class="button is-light is-rounded is-inverted">Download File</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="my-6" x-data="{ tab: 'link' }">
        <div class="columns">
            <div class="column is-6 is-offset-3">
                <div class="tabs is-centered is-boxed">
                    <ul>
                        <li :class="{ 'is-active': tab === 'link' }" @click="tab = 'link'">
                            <a>
                                <span class="icon is-small"><i class="fas fa-image" aria-hidden="true"></i></span>
                                <span>Link</span>
                            </a>
                        </li>
                        <li :class="{ 'is-active': tab === 'html' }" @click="tab = 'html'">
                            <a>
                                <span class="icon is-small"><i class="fas fa-film" aria-hidden="true"></i></span>
                                <span>HTML</span>
                            </a>
                        </li>
                        <li :class="{ 'is-active': tab === 'embed' }" @click="tab = 'embed'">
                            <a>
                                <span class="icon is-small"><i class="far fa-file-alt" aria-hidden="true"></i></span>
                                <span>Embed Code</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div x-show="tab === 'link'">
                    <div class="field">
                        <div class="control">
                            <textarea class="textarea">{{ route('file-show', $file->code) }}</textarea>
                        </div>
                    </div>
                </div>
                <div x-show="tab === 'html'">
                    <div class="field">
                        <div class="control">
                            <textarea class="textarea"><a href="{{ route('file-show', $file->code) }}" title="{{ $file->client_original_name }}">{{ $file->client_original_name }}</a> </textarea>
                        </div>
                    </div>
                </div>
                <div x-show="tab === 'embed'">
                    <div class="field">
                        <div class="control">
                            <textarea class="textarea"><iframe src="{{ route('embed-show', $file->code) }}" width="100%" height="310px"></iframe></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script type="text/javascript">
    document.title = "Download {{ $file->client_original_name }}"
</script>
</x-guest-layout>
