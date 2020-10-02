<div x-data="{ open: false}">
    <div class="field">
        <button @click="open = true" class="button is-small is-dark is-fullwidth">Kelola</button>
    </div>

    <div class="field"
        x-show="open"
        @click.away="open = false"
        @click.away="open = false"
    >
        <div class="buttons is-centered">
            <button class="button is-small is-success">Copy Embed</button>
            <button class="button is-small is-info">Edit</button>
            <button class="button is-small is-danger">Delete</button>
        </div>
    </div>
</div>
