<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <title inertia>{{ config('app.name', 'FHV Stream') }}</title>
        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="antialiased bg-gray-50 text-gray-900">
        @inertia
    </body>
</html>