<?php

return [

    // `null` serves /backend-docs outside production; `false` always answers 404; `true` always serves it.
    'enabled' => null,

    // URL path the docs are served at. Lowercase letters, digits, `-` and `_`, nested with `/`.
    // Any value other than `backend-docs` requires `php artisan drupal-backend-docs:publish`.
    'public_path' => 'backend-docs',

];
