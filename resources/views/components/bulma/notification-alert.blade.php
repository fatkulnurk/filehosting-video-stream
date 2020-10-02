<div>
    @if (session('status'))
        <div class="notification is-primary">
            {{ session('status') }}
        </div>
    @endif
    @if (session('success'))
        <div class="notification is-primary">
            {{ session('success') }}
        </div>
    @endif
</div>
