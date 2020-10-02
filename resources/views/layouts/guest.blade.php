<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="has-navbar-fixed-top">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">

    @livewireStyles

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.6.0/dist/alpine.js" defer></script>
    <script defer src="https://use.fontawesome.com/releases/v5.14.0/js/all.js"></script>

    <link href="https://vjs.zencdn.net/7.8.4/video-js.css" rel="stylesheet" />

    <!-- If you'd like to support IE8 (for Video.js versions prior to v7) -->
    <script defer src="https://vjs.zencdn.net/ie8/1.1.2/videojs-ie8.min.js"></script>
</head>
<body class="is-fullwidth">

<div class="navbar is-white is-fixed-top" role="navigation" aria-label="main navigation">
    <div class="container">
        <div class="navbar-brand">
            <a class="navbar-item" href="/">
                <h1 class="title is-4 has-text-primary is-uppercase">
                    <svg width="20" height="20" id="face-i-d" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M8,21H4a1,1,0,0,1-1-1V16" style="fill: none; stroke: rgb(0, 0, 0); stroke-linecap: round; stroke-linejoin: round; stroke-width: 2;"></path><path d="M16,21h4a1,1,0,0,0,1-1V16" style="fill: none; stroke: rgb(0, 0, 0); stroke-linecap: round; stroke-linejoin: round; stroke-width: 2;"></path><path d="M3,16v4a1,1,0,0,0,1,1H8" style="fill: none; stroke: rgb(0, 0, 0); stroke-linecap: round; stroke-linejoin: round; stroke-width: 2;"></path><path d="M3,8V4A1,1,0,0,1,4,3H8" style="fill: none; stroke: rgb(0, 0, 0); stroke-linecap: round; stroke-linejoin: round; stroke-width: 2;"></path><path d="M21,8V4a1,1,0,0,0-1-1H16" style="fill: none; stroke: rgb(0, 0, 0); stroke-linecap: round; stroke-linejoin: round; stroke-width: 2;"></path><path d="M12,7v3c0,1,0,3-2,3M8,9V7m8,2V7M8,16a6.08,6.08,0,0,0,8,0" style="fill: none; stroke: rgb(0, 0, 0); stroke-linecap: round; stroke-linejoin: round; stroke-width: 2;"></path></svg>
                    <span class="has-text-dark">{{ config('app.name') }}</span>
                </h1>
            </a>

            <a role="button" class="navbar-burger burger" aria-label="menu" aria-expanded="false"
               data-target="navbarBasicExample">
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
            </a>
        </div>

        <div id="navbarBasicExample" class="navbar-menu">
            <div class="navbar-end has-text-left">
                <div class="navbar-item">
                    <div class="buttons is-centered">
                        @auth
                            <a href="{{ route('dashboard.index') }}" class="button is-primary is-rounded">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="button is-primary is-rounded">Login</a>
                            <a href="{{ route('register') }}" class="button is-light is-rounded is-inverted">Sign Up</a>
                        @endauth
                    </div>
                </div>
                <div class="navbar-item has-dropdown is-hoverable">
                    <a class="navbar-item">
                        <div class="icon">
                            <i class="fa fa-bars"></i>
                        </div>
                    </a>

                    <div class="navbar-dropdown">
                        <a class="navbar-item">
                            Term of service
                        </a>
                        <a class="navbar-item">
                            Policy Privacy
                        </a>
                        <a class="navbar-item">
                            DMCA
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if (isset($header))
    <div class="hero is-fullwidth is-primary">
        <div class="hero-body is-vcentered">
            <div class="container">
                {{ $header }}
            </div>
        </div>
    </div>
@endif

<main class="is-fullwidth">
    {{ $slot }}
</main>

<footer class="footer">
    <div class="content has-text-centered">
        <p>
            &copy; 2020 - {{ config('app.name') }}
        </p>
    </div>
</footer>

@stack('modals')

@livewireScripts

<script>
    document.addEventListener('DOMContentLoaded', () => {

        // Get all "navbar-burger" elements
        const $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);

        // Check if there are any navbar burgers
        if ($navbarBurgers.length > 0) {

            // Add a click event on each of them
            $navbarBurgers.forEach( el => {
                el.addEventListener('click', () => {

                    // Get the target from the "data-target" attribute
                    const target = el.dataset.target;
                    const $target = document.getElementById(target);

                    // Toggle the "is-active" class on both the "navbar-burger" and the "navbar-menu"
                    el.classList.toggle('is-active');
                    $target.classList.toggle('is-active');

                });
            });
        }

    });
</script>
</body>
</html>

