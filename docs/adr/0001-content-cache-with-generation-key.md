# 0001. Content cache keyed by a global generation, invalidated by webhook

Status: accepted

## Context

Every page depends on Drupal JSON:API round-trips. Drupal exposes no ETag, `changed` field or push mechanism, and the production cache store is database-backed (no tags, no cheap key scan). No CDN or reverse proxy fronts the site.

## Decision

- A thin caching layer (`CachedDrupal`) sits between the fetchers and `DrupalClient` and stores decoded JSON:API document arrays, never models. Alias resolution is cached the same way, as `[endpoint, resourceName, bundle]`.
- Freshness is stale-while-revalidate: fresh 5 min, stale 24 h (also the stale-if-error window). Both are env-tunable.
- Every key includes one global content-generation counter. A signed webhook (`POST /hooks/drupal`, HMAC-SHA256 of `"{timestamp}.{raw body}"` with a timestamp tolerance, each signature accepted once, 404 while no secret is set) and `php artisan drupal:flush` replace it with a new UUID.
- Every `CachedDrupal` instance shares the store and the generation key, including the one wrapping the separate reviews site, so one flush invalidates all of them.
- Unknown aliases are cached as not-found for 60 s (`DRUPAL_CACHE_NEGATIVE`); unavailable and bad-response outcomes are never cached. An alias that resolves to the wrong kind of resource is not-found but is not cached negatively.

## Consequences

- Invalidation is coarse: any change refetches everything lazily. Acceptable at this site's size.
- Old generations expire by TTL; no purge query is needed.
- Until Drupal ships the webhook sender, only the fresh window and the flush command apply.
- Moving to a tag-capable store (Redis) would allow finer invalidation and needs separate approval.
- Search results are an exception to stale-while-revalidate: they use a short fresh-only window (60 s by default, no stale-if-error; `DRUPAL_CACHE_SEARCH=0` disables it) and the suggestions endpoint is not cached, because search keys are high-cardinality and users expect near-live results. Search keys still embed the generation, so a flush clears them. See ADR 0002 for how search queries are built.
- `DRUPAL_CACHE_ENABLED=false` bypasses every layer above.

## Addendum: one generation per Source

When the system serves several Drupal backends (Sources), each Source has its own generation key, replacing the single shared key stated above. A webhook for one Source bumps only that Source's generation, plus the generations of the Sources listed in its `flush.also.sources`. `drupal:flush` with no argument flushes every Source; `drupal:flush <source>` flushes one.

## Update (2026-10-25)

The decision stands. The code moved into the package and some names changed:

- `DrupalManager` hands out one `CachedDrupal` per Source, and `DrupalManager::flush()` follows `flush.also.sources`.
- Keys are `drupal:{source}:{generation}:{sha1}`. The generation lives under `drupal:generation:{source}`. The "global generation" in the Decision section is superseded by the addendum above.
- The fresh, stale, negative and search windows are shipped as defaults in the package's `config/drupal.php` and read from `drupal.cache.*` and `search.cache`.
- The webhook sender is specified by `php artisan drupal:contract`, which prints the payload, headers and a reference snippet. See the [Webhook and cache flushing](../webhook-and-cache-flushing.md) and [Caching model](../caching-model.md) pages.
