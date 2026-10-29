# Configuration reference

Every setting outside the content types: the first-level keys of `config/drupal-backend.php` and the shared `drupal.*` settings in `config/drupal.php`. Content type options are in the [Descriptor reference](./descriptor-reference.md).

## Before you start

[Install the package](./installation.md) and publish its config. The package warns about keys it does not know, so a typo such as `per_pge` shows up in `php artisan drupal:validate` and in the descriptor smoke test, with a suggestion.

Tables use these columns: the key in dot notation relative to its table, its type, the default the package applies when the key is missing, and the effect. `?string` means a string or `null`.

## The two config files

| File | Holds | Merge with package defaults |
|---|---|---|
| `config/drupal-backend.php` | The [descriptor](./vocabulary.md#descriptor) | First level only. A key you set replaces the package's whole section. |
| `config/drupal.php` | HTTP and cache settings shared by all Sources | Key by key. A partial file keeps the other defaults. |

## Top level

<<< @/snippets/fixtures/conference.php#sources{php}

| Key | Type | Default | Effect |
|---|---|---|---|
| `schema_version` | int | `2` | Must equal the version the package reads. Any other value stops the app at boot, also in production. |
| `sources` | map | `[]` | One Drupal backend per entry. See [Sources](#sources). |
| `default_source` | ?string | The Source named `default`, else the first one | Source used by content types without `source` and by code that asks for no particular Source. |
| `content_types` | map | `[]` | The content types. With none, the package registers no routes and does nothing. See the [Descriptor reference](./descriptor-reference.md). |
| `search` | map | `[]` | Search. See [Search](#search). |
| `routes` | map | `[]` | Content routes. See [Routes](#routes). |
| `webhook` | map | `[]` | Webhook defaults. See [Webhook](#webhook). |
| `limits` | map | `[]` | Rate limits. See [Rate limits](#rate-limits). |

## Sources

Each key of `sources` names a [Source](./vocabulary.md#source). The keys below are set per Source.

| Key | Type | Default | Effect |
|---|---|---|---|
| `base_url` | string | required | Origin of Drupal. A trailing slash is removed. It also prefixes image URIs and the Decoupled Router address. |
| `jsonapi_path` | string | `/jsonapi` | Path of the JSON:API root. Resolved paths must start with it. |
| `files_path` | string | `''` | Path of Drupal's public files. When set, `src` and `href` values in HTML fields that start with it are rewritten to absolute URLs on `base_url`. |
| `frontend_url` | ?string | `null` | Address of the frontend as Drupal knows it. Links to Drupal in HTML fields are rewritten to it, or to `APP_URL` when `null`. The contract shows it as the webhook host. |
| `webhook.secret` | ?string | `null` | Shared secret for the webhook. Empty means the Source's webhook answers 404. |
| `webhook.tolerance` | ?int | The global `webhook.tolerance` | Seconds of clock difference accepted for this Source. |
| `flush.also.sources` | list of strings | `[]` | Sources flushed together with this one. Follows chains and survives cycles. Unknown names fail validation. |
| `timeout` | int | `drupal.timeout` | Request timeout for this Source, in seconds. |
| `connect_timeout` | int | `drupal.connect_timeout` | Connection timeout for this Source, in seconds. |
| `retries` | int | `drupal.retries` | Extra attempts for this Source. |
| `retry_delay_ms` | int | `drupal.retry_delay_ms` | Pause between attempts for this Source, in milliseconds. |

The four HTTP keys override the shared `drupal.*` values for one Source. Cache windows cannot be overridden per Source. Source names used in the webhook URL may contain letters, digits, `_` and `-`.

## Search

The `search` section turns on the search page and suggestions. Validation of this section runs only when `enabled` is true. Settings of an installed search extension live in its own file; see the [Index extension](./index-extension.md).

<<< @/snippets/fixtures/conference.php#search{php}

| Key | Type | Default | Effect |
|---|---|---|---|
| `enabled` | bool | `false` | Registers the search page and suggestions routes and turns on search validation. |
| `tabs` | list of strings | searchable types in descriptor order | Content type keys shown as [tabs](./vocabulary.md#tab), in order. Each must be searchable and listed once. The first is the default tab. |
| `per_page` | int | `18` | Results per page. |
| `native_limit` | int | `50` | `page[limit]` of every native search and related request. JSON:API allows at most 50 on a default Drupal site. Configured, never discovered; `drupal:validate` prints it. |
| `cache` | int | `60` | Seconds a search result is served without refetching. `0` disables search caching. |
| `suggestions.limit` | int | `10` | Most suggestions in total and per type. |
| `suggestions.quotas` | map | `[]` | Maximum suggestions per content type key. Unused places go to the next tab. Unknown keys fail validation. |
| `term.min` | int | `2` | Fewest letters or digits in a usable term. Also the shortest suggestion query. The error message follows it. |
| `term.max` | int | `100` | Characters kept from the typed term. |
| `term.max_words` | int | `10` | Words kept from the typed term. |

## Routes

| Key | Type | Default | Effect |
|---|---|---|---|
| `auto` | bool | `true` | The package registers the content routes. Set `false` and call `Route::drupalContent()` yourself to place them. |
| `prefix` | string | `''` | URI prefix for content routes, the search page and suggestions. Not used for the webhook. |
| `middleware` | list of strings | `['web']` | Middleware of content routes, the search page and suggestions. The matching `throttle:` middleware is added. |
| `alias_pattern` | string | `[a-z0-9-]{1,128}` | Regular expression for the `{alias}` parameter. A URL that does not match answers 404 without calling Drupal. |

## Webhook

| Key | Type | Default | Effect |
|---|---|---|---|
| `path` | string | `/hooks/drupal` | Webhook path. Other Sources add `/{source}`. |
| `tolerance` | int | `30` | Seconds of clock difference accepted. A Source's `webhook.tolerance` wins. |

See [Webhook and cache flushing](./webhook-and-cache-flushing.md).

## Rate limits

Requests per minute and client IP. Redefine a limiter with `RateLimiter::for()` for more control.

| Key | Type | Default | Effect |
|---|---|---|---|
| `content` | int | `120` | Limiter `content`: item, listing and page routes and the search page. |
| `search_suggest` | int | `60` | Limiter `search-suggest`: the suggestions endpoint. |
| `drupal_webhook` | int | `60` | Limiter `drupal-webhook`: the webhook routes. |

## Documentation route

The documentation is served by the separate `proteo/drupal-backend-docs` package, which reads `config/drupal-backend-docs.php`. It is not a key of `config/drupal-backend.php`.

| Key | Type | Default | Effect |
|---|---|---|---|
| `enabled` | ?bool | `null` | `null` serves `/backend-docs` everywhere except production. `true` always serves it. `false` always answers 404. |

## Shared settings

These settings live in `config/drupal.php` and apply to every Source. The package ships the defaults; each reads an environment variable.

| Key | Type | Default | Effect |
|---|---|---|---|
| `drupal.connect_timeout` | int | `3` | Seconds to wait for a connection. Variable `DRUPAL_CONNECT_TIMEOUT`. |
| `drupal.timeout` | int | `10` | Seconds to wait for a whole request. Variable `DRUPAL_TIMEOUT`. |
| `drupal.retries` | int | `2` | Extra attempts for a GET request after a connection error or a 5xx answer. Variable `DRUPAL_RETRIES`. |
| `drupal.retry_delay_ms` | int | `200` | Pause between attempts, in milliseconds. Variable `DRUPAL_RETRY_DELAY_MS`. |
| `drupal.cache.enabled` | bool | `true` | `false` bypasses every cache layer. Variable `DRUPAL_CACHE_ENABLED`. |
| `drupal.cache.fresh` | int | `300` | Seconds an entry is served without revalidation. Variable `DRUPAL_CACHE_FRESH`. |
| `drupal.cache.stale` | int | `86400` | Seconds past the fresh window that a stale copy is served. Variable `DRUPAL_CACHE_STALE`. |
| `drupal.cache.negative` | int | `60` | Seconds an unknown alias is remembered. Variable `DRUPAL_CACHE_NEGATIVE`. |
| `drupal.cache.store` | ?string | `null` | Cache store for package entries. `null` uses the default store. Variable `DRUPAL_CACHE_STORE`. |
| `drupal.related.max_requests` | int | `12` | Most requests one related content call may send (target types times criteria). More throws `InvalidRelatedRequest`. Variable `DRUPAL_RELATED_MAX_REQUESTS`. |

The package reads no other environment variables. Names such as `DRUPAL_BASE_URL` and `WEBHOOK_SECRET` are conventions of your own `config/drupal-backend.php`; see [Installation](./installation.md).

## Next steps

- Describe your content in the [Descriptor reference](./descriptor-reference.md).
- Understand the cache settings in the [Caching model](./caching-model.md).
- Check your configuration with [Backend setup](./backend-setup.md).
