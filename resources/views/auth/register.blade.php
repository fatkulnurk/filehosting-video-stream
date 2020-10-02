<x-guest-layout>
    <section class="section">
        <div class="columns">
            <div class="column is-4 is-offset-4">
                <div class="box">
                    <form method="POST" action="{{ route('register') }}">
                        @csrf
                        <div class="field">
                            <label for="" class="label">{{ __('Name') }}</label>
                            <div class="control">
                                <input class="input" type="text" placeholder="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" />
                            </div>
                        </div>

                        <div class="field">
                            <label for="" class="label">{{ __('Email') }}</label>
                            <div class="control">
                                <input class="input" type="email" placeholder="email" name="email" value="{{ old('email') }}" required />
                            </div>
                        </div>

                        <div class="field">
                            <label for="" class="label">{{ __('Password') }}</label>
                            <div class="control">
                                <input class="input" type="password" placeholder="password" name="password" required autocomplete="new-password" />
                            </div>
                        </div>

                        <div class="field">
                            <label for="" class="label">{{ __('Confirm Password') }}</label>
                            <div class="control">
                                <input class="input" placeholder="password" type="password" name="password_confirmation" required autocomplete="new-password" />
                            </div>
                        </div>

                        <div class="field">
                            <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('login') }}">
                                {{ __('Already registered?') }}
                            </a>
                        </div>

                        <div class="field">
                            <button class="button is-primary is-fullwidth">
                                {{ __('Register') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-guest-layout>
