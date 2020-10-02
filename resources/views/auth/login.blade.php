<x-guest-layout>
    <section class="section">
        <div class="columns">
            <div class="column is-4 is-offset-4">
                <div class="box">
                    @if (session('status'))
                        <div class="mb-4 font-medium text-sm text-green-600">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="field">
                            <label for="" class="label">{{ __('Email') }}</label>
                            <div class="control">
                                <input class="input" type="email" name="email" value="{{old('email')}}" placeholder="Email" required autofocus />
                            </div>
                        </div>

                        <div class="mt-4">
                            <label for="" class="label">{{ __('Password') }}</label>
                            <div class="control">
                                <input class="input" type="password" name="password" required autocomplete="current-password" />
                            </div>
                        </div>

                        <div class="field">
                            <label class="control">
                                <input type="checkbox" class="form-checkbox" name="remember">
                                <span class="ml-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                            </label>
                        </div>

                        <div class="field">
                            @if (Route::has('password.request'))
                                <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('password.request') }}">
                                    {{ __('Forgot your password?') }}
                                </a>
                            @endif
                        </div>
                        <div class="field">
                            <button class="button is-primary is-fullwidth">{{ __('Login') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-guest-layout>
