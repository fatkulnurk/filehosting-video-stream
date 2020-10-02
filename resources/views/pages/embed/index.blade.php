<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="has-navbar-fixed-top">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $file->name }}</title>
    <link href="https://vjs.zencdn.net/7.8.4/video-js.css" rel="stylesheet" />
    <script defer src="https://vjs.zencdn.net/ie8/1.1.2/videojs-ie8.min.js"></script>

    <style>
        #instructions {
            max-width: 640px;
            text-align: left;
            margin: 0px auto;
        }

        #instructions textarea {
            width: 100%;
            height: 100px;
        }
        /* Show the controls (hidden at the start by default) */

        .video-js .vjs-control-bar {
            display: -webkit-box;
            display: -webkit-flex;
            display: -ms-flexbox;
            display: flex;
        }
        /* Make the demo a little prettier */

        body {
            background: #222;
            text-align: center;
            color: #aaa;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            background: radial-gradient(#333, hsl(200, 30%, 6%));
            width: 100%;
            height: 100%;
        }

        a,
        a:hover,
        a:visited {
            color: #76DAFF;
        }

        .video-js {
            position: fixed;
            right: 0;
            bottom: 0;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            /* z-index: -100; */
            /* background: url(polina.jpg) no-repeat; */
            background-color: black;
            background-size: cover;
            z-index:99999;
        }

    </style>
</head>
<body>

<video
    id="my-video"
    class="video-js "
    controls
    preload="auto"
    width="100vw"
    height="264"
    {{--                poster="MY_VIDEO_POSTER.jpg"--}}
    data-setup="{}"
>
    <source src="{!! route('storage-url', [
    'code' => $file->code,
    'hash' => $file->path_hash,
    'key' => $file->expired_time
    ]) !!}" type="video/mp4">
{{--    <source src="//vjs.zencdn.net/v/oceans.webm" type="video/webm">--}}
    <p class="vjs-no-js">
        To view this video please enable JavaScript, and consider upgrading to a
        web browser that
        <a href="https://videojs.com/html5-video-support/" target="_blank"
        >supports HTML5 video</a
        >
    </p>
</video>
<script defer src="https://vjs.zencdn.net/7.8.4/video.js"></script>
<script>
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
    });
</script>
</body>
</html>

