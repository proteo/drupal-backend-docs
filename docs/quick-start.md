# Quick start

Render one Drupal content type, `talk`, in a Laravel app. You declare it, let the package register the routes, and prove it works with a test that fakes Drupal. This takes about ten minutes.

## Before you start

- A Laravel 13 app on PHP 8.4 with Livewire 4.3 or later.
- The [package repository](./installation.md) configured in Composer.
- No Drupal site yet. The test supplies the responses; you connect a real backend at the end.

## 1. Install the package

```bash
composer require proteo/drupal-backend
php artisan vendor:publish --tag=drupal-backend-config
```

Check: `config/drupal-backend.php` and `config/drupal.php` now exist. The first holds your [descriptor](./vocabulary.md#descriptor); the second holds timeouts and cache windows that already have working defaults.

## 2. Describe the talk content type

Replace the contents of `config/drupal-backend.php`:

<<< @/snippets/quick-start/drupal-backend.php{php}[config/drupal-backend.php]

Then add the backend address to `.env`:

```ini [.env]
DRUPAL_BASE_URL=https://drupal.example
```

Check: `php artisan about` starts without an error. A broken descriptor stops the app at boot and lists every problem.

## 3. See the routes

The package registers routes for every [primary content type](./vocabulary.md#role). You write no route file.

```bash
php artisan route:list --name=talk
```

Check: the output lists `talk.index` for `GET /talks` and `talk` for `GET talk/{alias}`.

## 4. Test the pages

Add a feature test. `FakesDrupal` builds the JSON:API documents Drupal would answer for your own descriptor, and `Http::fake()` serves them.

<<< @/snippets/quick-start/TalkPagesTest.php{php}[tests/Feature/TalkPagesTest.php]

```bash
php artisan test --filter=TalkPagesTest
```

Check: both tests pass. The package's default views render the first field of the content type as the heading. Replacing them is covered in [Views and overrides](./views-and-overrides.md).

## 5. Connect a real backend

Point `DRUPAL_BASE_URL` at your Drupal site, then ask the package what that site needs and whether it has it:

```bash
php artisan drupal:contract
php artisan drupal:validate
```

Check: `drupal:validate` prints a `PASS` for every check, or tells you what to fix. [Backend setup](./backend-setup.md) explains each requirement.

## Next steps

- Add speakers, topics and images in [Mapping content](./mapping-content.md).
- Keep the cache current with the [webhook](./webhook-and-cache-flushing.md).
- Read the [Vocabulary](./vocabulary.md) for the terms used above.
