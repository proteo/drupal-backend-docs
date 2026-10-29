# Index extension

`proteo/drupal-search-api` adds a Drupal Search API index to the search and related content that core answers from native collections. It is a separate package, installed next to `proteo/drupal-backend`, and its documentation lives in its own repository. This page says when to install it and what it changes.

## Before you start

- Read [Search setup](./search-setup.md), especially [Core only or with the index extension](./search-setup.md#core-only-or-with-the-index-extension).
- Install the extension only when a native limit hurts: term matches beyond `search.native_limit`, counts that read `N+`, or a boolean flag that exists only in the index.

## What it adds

| Area | With the extension |
|---|---|
| Counts | Exact, from the index's `meta.count`. |
| Filter-only search | Answered from the index in one request. |
| Typed term | Still matched natively, or whole-word `index_fulltext` from the index per content type. |
| Boolean filters | May name a column that exists only in the index; core refuses such a filter. |
| Related content | One ranked query against the index, with a candidate pool you can tune through `RelatedRequest::$extras`. |
| `drupal:validate` | Index probes: the index path and `meta.count`, the bundle, every column and sort. |
| `drupal:contract` | The Search API modules and the index columns of each content type. |

## 1. Install

```bash
composer require proteo/drupal-search-api
```

The service provider is discovered automatically. It wraps the `SearchBackend` and `RelatedStrategy` bindings with `extend()`, so the order of providers does not matter, and it tags its checks and contract lines. Only one extension can rebind `SearchBackend`.

## 2. Describe the index

The extension reads its own file, `config/drupal-search-api.php`, keyed by your content type keys. The native halves of filters, such as `native_path`, stay in `config/drupal-backend.php`.

Check: `php artisan drupal:validate` ends with an `extensions` scope that names the index backend and strategy and reports exact counts and fulltext as `yes`.

## What does not change

- The descriptor, the routes, the views and the component props are the same.
- The 60-second search cache and the content generation key apply to index requests too. The index lags Drupal by whatever Search API takes to reindex, so a fresh edit may show later in an index answer than in a native one.

## Remove it

Uninstall the package and delete `config/drupal-search-api.php`. Core returns to native search. A filter that relied on an index-only boolean then fails validation until you give it a `native` path.

## Next steps

- Follow the extension's own documentation in its repository: install, index setup, configuration reference, behaviour, troubleshooting and upgrading from core 2.x.
- Compare the modes in [Search setup](./search-setup.md#core-only-or-with-the-index-extension).
- Read the decision in [ADR 0002](./adr/0002-search-on-native-collections-with-index-for-flags.md).
