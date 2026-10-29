# Routes and controllers

The package registers the routes your descriptor implies, plus a webhook, optional search routes and a documentation route. This page lists every route name, the controllers behind them and how to take over registration.

## Before you start

Declare at least one content type, as in the [Quick start](./quick-start.md). With none, the package registers no content, webhook or search routes.

## Route names

List the registered routes with `php artisan route:list --name=talk`. These are all the names the package can register:

| Name | Method and URI | Registered when |
|---|---|---|
| `{key}.index` | `GET {listing_path}` | A primary or facet type sets `listing_path` |
| `{key}` | `GET {url_prefix}/{alias}` | A primary or facet type exists. With an empty `url_prefix` the URI is `/{alias}`. |
| `page.{slug}` | `GET` the URL in the page type's `routes` map | A page type declares `routes` |
| `hooks.drupal` | `POST {webhook.path}` | Always, for the default Source |
| `hooks.drupal.source` | `POST {webhook.path}/{source}` | Always, for any Source |
| `search` | `GET {prefix}/search` | `search.enabled` is true |
| `search.suggest` | `GET {prefix}/search/suggest` | `search.enabled` is true |
| `drupal-backend-docs.docs` | `GET /backend-docs/{path?}` | Registered by `proteo/drupal-backend-docs`, not by this package. Answers 404 in production unless `drupal-backend-docs.enabled` is `true`. |

`{key}` is the content type key, so a `talk` type gives the routes `talk.index` and `talk`. Use them with `route('talk', $alias)`.

## Controllers and view data

Each route is served by a plain controller. It picks the first view that exists in a [chain](./views-and-overrides.md) and passes the data below.

| Route | Controller | Fetches | View data |
|---|---|---|---|
| `{key}` | `ContentItemController` | The item, through `ContentFetcher::item()` | `{key}`, `entity`, `type`, `seo` |
| `{key}.index` | `ContentListingController` | Nothing. The `drupal::listing` Livewire component fetches each page. | `type` |
| `page.{slug}` | `ContentPageController` | The page, with the slug as the alias | `{key}`, `entity`, `type`, `seo` |
| `search` | `SearchPageController` | Nothing. The `drupal::search` component does. | none |
| `search.suggest` | `SearchSuggestionsController` | Suggestions for the query `q` | JSON list of `type`, `title`, `url`, `subtitle` |
| `hooks.drupal`, `hooks.drupal.source` | `DrupalWebhookController` | Nothing. It flushes the Source. | Empty `202` answer |

The webhook routes sit outside the `web` middleware group, so they have no session and no CSRF check. See [Webhook and cache flushing](./webhook-and-cache-flushing.md).

## Prefix, middleware and alias pattern

Three `routes` settings shape every content route, the search page and the suggestions:

| Setting | Default | Effect |
|---|---|---|
| `routes.prefix` | `''` | URI prefix, for example `shop`. The webhook ignores it. |
| `routes.middleware` | `['web']` | Middleware of the routes. |
| `routes.alias_pattern` | `[a-z0-9-]{1,128}` | Allowed shape of `{alias}`. Other URLs are a plain 404 and never reach Drupal. |

The package adds the rate limiter: `throttle:content` for content and the search page, `throttle:search-suggest` for suggestions. See the [Configuration reference](./configuration-reference.md#routes).

## Pick one: automatic routes or `Route::drupalContent()`

By default the package registers content routes itself. To place them yourself, for example in your own middleware group or order, turn that off and call the macro:

```php [config/drupal-backend.php]
'routes' => ['auto' => false],
```

```php [routes/web.php]
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:content')->group(function () {
    Route::drupalContent('talk', 'speaker', 'topic', 'track', 'page');
});
```

`Route::drupalContent()` with no arguments registers every primary, facet and page type. With keys, it registers only those. Use one approach, not both: with `routes.auto` on, the macro call is redundant, and it gives you no control over order or middleware.

Webhook and search routes are always registered by the package. Only content routes follow `routes.auto`.

A type with an empty `url_prefix` serves items at `/{alias}`, which matches any single segment that fits `alias_pattern`. Register your own fixed routes first, or place the macro call last.

## Route caching

`php artisan route:cache` works. The package skips its own registration when routes are cached, and the documentation route is part of the cache. Run the cache command after the descriptor is final.

## Errors

A failed fetch becomes a 404, 502 or 503 response. The controllers render your `errors.{status}` view first and fall back to `drupal::error`. See [Architecture](./architecture.md#when-drupal-fails).

## Next steps

- Customise the pages in [Views and overrides](./views-and-overrides.md).
- Put the search page behind your layout in [Search setup](./search-setup.md).
- Look up every routing option in the [Configuration reference](./configuration-reference.md).
