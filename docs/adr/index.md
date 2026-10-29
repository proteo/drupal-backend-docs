# ADR index

Architecture decision records explain why the package works the way it does. Each record keeps its original text and gains a dated update note when the code changes names or details. Read a record before you propose a change to the area it covers.

## Before you start

Know the terms [content generation](../vocabulary.md#content-generation), [native collection](../vocabulary.md#native-collection) and [search backend](../vocabulary.md#search-backend).

## Records

| Record | Status | Decision | Read with |
|---|---|---|---|
| [0001. Content cache keyed by a global generation, invalidated by webhook](./0001-content-cache-with-generation-key.md) | Accepted | Cache decoded JSON:API documents with stale-while-revalidate. Put a content generation in every key and replace it from a signed webhook or `drupal:flush`. | [Caching model](../caching-model.md), [Webhook and cache flushing](../webhook-and-cache-flushing.md) |
| [0002. Typed terms search the native collections; the index serves filters and flags](./0002-search-on-native-collections-with-index-for-flags.md) | Accepted | Match a typed term on the native collections so partial words work. Since 3.0.0 the index is an optional extension that serves filters, flags and exact totals. | [Search setup](../search-setup.md) |

## Update notes

The records keep their history, so the body text can name classes that no longer exist. The update note at the end of each record gives the current names.

### 0001: cache generation

- The generation is per Source, not global: keys are `drupal:{source}:{generation}:{hash}`. The addendum in the record introduced this.
- `DrupalManager` hands out one `CachedDrupal` per Source and follows `flush.also.sources` when it flushes.
- The cache windows ship as defaults in the package's `config/drupal.php`.
- `drupal:contract` specifies the webhook sender.

### 0002: search

- `SearchFetcher` replaces `SearchFetchService`, with `SearchTabs`, `SearchSorts`, `SearchFilterSet` and `SearchQueries`.
- The relevance weights are the `term_fields` and `related_term_fields` of each searchable content type.
- The match strategy is a setting: `native_contains` is this record's decision and the default, and `index_fulltext` suits a backend whose index matches partial words.
- Since 2.0.0 any number of types are searchable; the strategy and the index lookup are per type, and filters and sorts apply on their own type's tab.
- Since 3.0.0 core searches native collections only, behind the `SearchBackend` seam. The index moved to `proteo/drupal-search-api`; counts carry an `exact` flag and filter-only searches page natively.

## Next steps

- Understand the cache in the [Caching model](../caching-model.md).
- Set up search in [Search setup](../search-setup.md).
- Read the package vocabulary in [Vocabulary](../vocabulary.md).
