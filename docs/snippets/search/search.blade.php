<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>Search</title>
    @livewireStyles
</head>
<body>
    <h1>Search the library</h1>
    <livewire:drupal::search/>
    @livewireScripts
</body>
</html>
