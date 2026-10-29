<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>Talks</title>
</head>
<body>
    <h1>Talks</h1>
    <livewire:drupal::listing content-type="talk"/>
</body>
</html>
