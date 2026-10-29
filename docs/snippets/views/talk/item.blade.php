<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $talk->title }}</title>
</head>
<body>
    <article>
        <h1>{{ $talk->title }}</h1>
        {!! $talk->summary !!}
    </article>
</body>
</html>
