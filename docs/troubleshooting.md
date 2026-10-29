# Troubleshooting

Find a symptom or an exception, learn its cause and apply the fix. Visitors only ever see generic messages; the detail is in your application log.

## Before you start

Run `php artisan drupal:validate`. Most problems below show up there as a `FAIL` with a `Fix:` line. See [Backend setup](./backend-setup.md#check-the-backend). Set `DRUPAL_CACHE_ENABLED=false` while you debug, so every request reaches Drupal.

## Symptoms

| Symptom | Likely cause | Fix |
|---|---|---|
| A page answers 404 for content that exists in Drupal | [`DrupalNotFound`](#drupalnotfound): anonymous users cannot read it, the alias does not resolve, or the URL prefixes disagree | Check [anonymous access](./backend-setup.md#_2-allow-anonymous-reading) and the [prefixes](./mapping-content.md#_4-set-the-routes) |
| Every page answers 503 | [`DrupalUnavailable`](#drupalunavailable): Drupal is down, slow or rate-limiting | Check `DRUPAL_BASE_URL`, timeouts and Drupal's health |
| A page answers 502 | [`DrupalBadResponse`](#drupalbadresponse): a required field is missing or the answer is not JSON:API | Compare with `php artisan drupal:contract` |
| The app fails at boot with a list of problems | [`InvalidDescriptor`](#invaliddescriptor) | Fix the listed keys |
| A view fails with "has no field or relation named" | [`UnknownAttribute`](#unknownattribute) | Use the names from the descriptor |
| Edits in Drupal do not appear | The webhook is not arriving, or the fresh window has not ended | See [Content does not update](#content-does-not-update) |
| The webhook answers 404 or 401 | No secret, a clock difference, a wrong signature or a replay | See [Webhook is refused](#webhook-is-refused) |
| Related items are missing | The item shares nothing with others, the relation is not on the target type, or a request failed (look for `Related content fetch failed` in the log) | See [Related content](./related-content.md) |
| A tab count ends in `+` | The package saw only part of the matches: a native request was cut at `search.native_limit`, or more pages follow | Narrow the search, or install the [Index extension](./index-extension.md) for exact counts |
| `/backend-docs` answers 404 | Production, or `drupal-backend-docs.enabled` is `false` | See [Documentation route](#documentation-route-answers-404) |
| Uppercase URLs answer 404 | `alias_pattern` accepts lowercase only | Redirect to lowercase in your app, or change the pattern |

## Exceptions from Drupal

All three extend `DrupalException`. Their messages are generic. The log entry carries a context with some of these keys.

<details>
<summary>Context keys of a Drupal exception</summary>

| Key | Meaning |
|---|---|
| `url` | The request that failed |
| `status` | The HTTP status, or `null` when there was no answer |
| `duration_ms` | Time spent, including retries |
| `attempts` | Attempts made |
| `error` | The connection error message |
| `reason` | A short reason, such as `wrong resource type` or `cached miss` |

</details>

### DrupalNotFound

**Status 404.** Raised when:

- Drupal answers 404 or 403 for a request. The package treats a forbidden resource as missing.
- The Decoupled Router returns no resource for the alias (`reason: no resource for path`).
- The alias resolves to a different content type than the route expects (`reason: wrong resource type`).
- The alias was recently unknown and is remembered (`reason: cached miss`).

Fix:

1. Open the JSON:API URL from the log in a private browser window. A 403 means anonymous users lack read permission.
2. Check `alias_prefix`: the package resolves `/{alias_prefix}/{alias}`, and an empty prefix resolves `/{alias}`.
3. If you just published the item, the unknown alias is remembered for `drupal.cache.negative` seconds. Run `php artisan drupal:flush` or wait.

Laravel reports it like any exception unless your app calls `dontReport()`, as a busy site usually does.

### DrupalUnavailable

**Status 503 with `Retry-After: 30`.** Raised on a connection error or timeout, after the retries are used up, and on status 429 or 5xx.

Fix:

1. Check that `DRUPAL_BASE_URL` is reachable from the server: `curl -I $DRUPAL_BASE_URL/jsonapi`.
2. If Drupal is slow, raise `DRUPAL_TIMEOUT` or the Source's `timeout`. Retries apply to connection errors and 5xx answers, up to `DRUPAL_RETRIES` extra attempts.
3. A 429 means Drupal or its proxy is rate-limiting you. Raise the limit there.

Cached content keeps being served during an outage until the stale window ends, 24 hours by default. Only uncached requests fail.

### DrupalBadResponse

**Status 502.** Raised when:

- Drupal answers with another status such as 400.
- The body is not JSON, or has no `data` array.
- A `required` field is missing or empty, or a link has no `url`.
- The Decoupled Router returns a path outside `jsonapi_path`.

Fix: run `php artisan drupal:validate`; it names the missing field, relationship or index column. Check `jsonapi_path` if every request fails. Mark a relation `optional` to drop it instead of failing the page.

## Errors in your code

### InvalidDescriptor

Raised at boot with a list of problems, or with `Invalid drupal-backend descriptor at [path]: reason` for a malformed value. A `schema_version` mismatch always stops the app. Rule violations are logged in production instead.

<details>
<summary>Boot failure output</summary>

```text
The drupal-backend descriptor has 2 problem(s):
- sources: the default source [default] is not declared.
- content_types.talk: ...
```

</details>

Fix each listed key. The [Descriptor reference](./descriptor-reference.md#what-validation-checks) lists the rules. An unknown key (a typo) is a warning, not a failure: `drupal:validate` prints it with a suggestion.

### UnknownAttribute

A view read `$talk->name` and the type declares no field or relation called `name`. A declared field that is empty returns `null`; only an undeclared name throws.

Fix: use the names from `fields` and `relations`, or add the field to the descriptor.

### Other programmer errors

| Message | Cause |
|---|---|
| `Unknown content type [x].` / `Unknown source [x].` | A key that the descriptor does not declare |
| `Content type [x] declares no per_page.` | A listing of a type without `per_page` |
| `Content type [x] declares no title_attribute.` | Filter options for a type without it |
| `Entities are immutable.` | Setting a property on an `Entity` |
| `InvalidRelatedRequest` | A `<x-drupal::related>` call the descriptor cannot serve: an unknown type or relation, relations that point to different kinds of entity, an `order` attribute a target type lacks, no criteria, a limit below 1, or more requests than `drupal.related.max_requests`. The message names the cause; it is a 500, not an empty list |

## Content does not update

1. Open the page, then `php artisan drupal:flush`, then reload. If the new content appears, the cache is the issue.
2. Without a webhook, content updates when the fresh window ends (`drupal.cache.fresh`, 300 seconds by default).
3. With a webhook, look for `Drupal content cache flushed by webhook` in the log after an edit. If it is missing, check the sender in Drupal against [Webhook and cache flushing](./webhook-and-cache-flushing.md).
4. If a flush works but pages stay stale, a CDN or proxy in front of the app caches them.

## Webhook is refused

The log line `Drupal webhook rejected` names the reason:

| Reason | Status | Fix |
|---|---|---|
| `secret_not_configured` | 404 | Set `webhook.secret` on that Source, or check the Source name in the URL. |
| `timestamp_invalid_or_outside_tolerance` | 401 | Sync the clocks, or raise `webhook.tolerance`. |
| `signature_mismatch` | 401 | Sign the exact bytes sent with the same secret. A proxy that rewrites the body breaks it. |
| `replayed_signature` | 401 | The same signed request arrived twice, for example from a retry. Sign each attempt again with a fresh timestamp. |

A 429 means the `drupal-webhook` rate limit was exceeded.

## Documentation route answers 404

`/backend-docs` is served outside production only, unless `drupal-backend-docs.enabled` is `true`. It is also absent when routes are cached before the package was upgraded: run `php artisan route:clear` and cache again.

## Next steps

- Check the backend with [Validation in CI/deploy](./validation-in-ci-deploy.md).
- Review the exceptions in the [Vocabulary](./vocabulary.md#failures).
- Read the [Caching model](./caching-model.md) to understand stale content.
