# Installation

Add `proteo/drupal-backend` to a Laravel app, publish its two config files and point it at your Drupal site. The package registers itself, so no provider or route file needs editing.

## Before you start

- A Laravel 13 app on PHP 8.4. The package installs Livewire 4.3 or later as a dependency.
- A Drupal 11.3 or later site with core JSON:API and the Decoupled Router module. [Backend setup](./backend-setup.md) lists the rest; you can install the package before the backend is ready.
- A cache store that supports atomic locks and survives between requests, such as `database`, `redis` or `file`. The `array` store forgets everything after each request. See [Choose a cache store](#choose-a-cache-store).
- Read access to the package's Git repository.

## 1. Add the package repository

Composer installs the package from the Git repository that hosts it. Add that repository to your app's `composer.json`, replacing the URL with yours:

```json [composer.json]
{
    "repositories": [
        {
            "name": "drupal-backend",
            "type": "vcs",
            "url": "git@git.example.com:proteo/drupal-backend.git"
        }
    ]
}
```

Check: `composer show --available proteo/drupal-backend` lists the released versions.

## 2. Require the package

```bash
composer require proteo/drupal-backend
```

Laravel discovers the service provider on its own. Check: `composer show proteo/drupal-backend` prints the installed version.

## 3. Publish the configuration

```bash
php artisan vendor:publish --tag=drupal-backend-config
```

The track publishes two files:

| File | Holds | You edit it to |
|---|---|---|
| `config/drupal-backend.php` | The [descriptor](./vocabulary.md#descriptor): Sources, content types, search, routes, webhook and rate limits | Describe your Drupal content. Every project edits this file. |
| `config/drupal.php` | Shared HTTP and cache settings for every Source | Change timeouts, retries or cache windows. Most projects leave it alone and use `.env`. |

The package ships working defaults for `config/drupal.php` and merges them under your copy, one key at a time. You can delete keys you do not change, or skip publishing the file. `config/drupal-backend.php` is different: the package merges only its first level, so a published copy replaces whole sections. See the [Configuration reference](./configuration-reference.md).

## 4. Point the package at Drupal

Read the backend address and secret from the environment:

<<< @/snippets/installation/drupal-backend.php{php}[config/drupal-backend.php]

```ini [.env]
DRUPAL_BASE_URL=https://drupal.example
WEBHOOK_SECRET=
APP_URL=https://www.example
```

- Pass `env()` values as they are. The package trims slashes, casts numbers and applies defaults itself, so the config file needs no `rtrim()`, `trim()` or `(int)`.
- `DRUPAL_BASE_URL` is the origin of Drupal, without a path.
- `WEBHOOK_SECRET` stays empty until you set up the [webhook](./webhook-and-cache-flushing.md). While it is empty, the webhook route answers 404.
- `APP_URL` is your backendend's address. The package rewrites links to Drupal inside rich text to this address unless a Source sets `backendend_url`.

Check: with at least one content type declared, `php artisan route:list --name=hooks.drupal` lists the webhook route.

## 5. Declare a content type

Follow the [Quick start](./quick-start.md) to declare `talk` and see its routes. With no content types the package registers no routes and does nothing.

## Choose a cache store

The package caches every Drupal response in Laravel's cache. It refreshes stale entries after the response is sent, and it takes a lock to do so, so the store must support atomic locks.

| Store | Use it for |
|---|---|
| `redis`, `database`, `file` | Any environment that serves real traffic. |
| `array` | Tests only. |

Use the app's default store, or give the package its own:

```ini [.env]
DRUPAL_CACHE_STORE=redis
```

Leave the variable unset to use the default store. [Caching model](./caching-model.md) explains the windows.

## Environment variables

All variables are optional. The defaults come from the package's `config/drupal.php`.

| Variable | Config key | Default | Effect |
|---|---|---|---|
| `DRUPAL_CONNECT_TIMEOUT` | `drupal.connect_timeout` | `3` | Seconds to wait for a connection. |
| `DRUPAL_TIMEOUT` | `drupal.timeout` | `10` | Seconds to wait for a whole request. |
| `DRUPAL_RETRIES` | `drupal.retries` | `2` | Extra attempts after a connection error or a 5xx answer. |
| `DRUPAL_RETRY_DELAY_MS` | `drupal.retry_delay_ms` | `200` | Pause between attempts, in milliseconds. |
| `DRUPAL_CACHE_ENABLED` | `drupal.cache.enabled` | `true` | `false` bypasses every cache layer. |
| `DRUPAL_CACHE_FRESH` | `drupal.cache.fresh` | `300` | Seconds a cached response is served without revalidation. |
| `DRUPAL_CACHE_STALE` | `drupal.cache.stale` | `86400` | Seconds past the fresh window that a stale copy is still served. |
| `DRUPAL_CACHE_NEGATIVE` | `drupal.cache.negative` | `60` | Seconds an unknown alias is remembered. |
| `DRUPAL_CACHE_STORE` | `drupal.cache.store` | default store | Cache store for all package entries. |

## Behaviour to know about

- **Broken descriptors.** Outside production, a descriptor with rule violations stops the app at boot and lists every problem. In production the package logs each one as an error and keeps serving, so a config slip does not take the site down. Malformed values, such as a missing `base_url` or a wrong `schema_version`, stop the app in every environment.
- **Route and config caching.** `php artisan route:cache` and `config:cache` work. Run them after the descriptor is final.
- **Documentation route.** This documentation is not part of the package. Install `proteo/drupal-backend-docs` as a dev dependency and it serves the pages at `/backend-docs` outside production. Set `enabled` to `false` in `config/drupal-backend-docs.php` to turn it off everywhere.
- **SEO metadata.** The `seo` option of a content type needs `ralphjsmit/laravel-seo`, which the package only suggests.

<details>
<summary>Boot failure output</summary>

A malformed value names its path. A missing base URL, for example when `DRUPAL_BASE_URL` is not set, stops the app with:

```text
Invalid drupal-backend descriptor at [sources.default.base_url]: a string is required
```

</details>

## Next steps

- Render your first content type with the [Quick start](./quick-start.md).
- Prepare Drupal in [Backend setup](./backend-setup.md).
- See how a request flows through the package in [Architecture](./architecture.md).
