# 0002. Typed terms search the native collections; the index serves filters and flags

Status: accepted

## Context

The Drupal Search API index (`index/default`) matches whole words only ("class" does not find "Classics"), and it is the only place that holds boolean index fields and a total count in `meta.count`. The native JSON:API collections support `CONTAINS`, so they match partial words, but filtering them on a related entity's name joins the relationship and silently drops entities that have none. Neither source alone gives the search we want.

## Decision

- A typed term is matched on the native collections for every tab: `CONTAINS` per word on the type's `term_fields`. `filter[fulltext]` is not sent for typed terms.
- A type that also matches related names (for example a talk and its speakers, topics and tracks) sends one native request per related path, merged and scored in PHP by the weights, so a related-name join cannot hide a title match.
- A search with filters but no term goes to the index. Boolean index fields that have no native path exist only in the index, so with a term the index supplies the set of matching ids and the term matches are intersected with it.
- Tab counts are fetched concurrently with the shown page (`DrupalClient::pool()`), and the search cache (60 s, fresh only) stores each request.
- Suggestions use the native collections directly, uncached.

## Consequences

- Partial matching and relevance ranking work, at the cost of one request per related path and one per other tab.
- Each native request is capped at 50 results (the JSON:API page size ceiling), so term matches beyond 50 are unreachable and term-search pagination is an in-memory slice of that list. Counts are the number of matches found, not the true total.
- Entries that fail a condition are removed in PHP after fetching unless the rule is `filterable`, because a computed value cannot be filtered on in native collections.
- The index lookup reads up to `search.index_lookup_pages` pages (10 by default, 500 ids). Beyond that, matching items are missed.
- Relevance is approximated by the search fetcher's weights, which mirror the index's but are maintained by hand.
- Moving to an index that matches partial words (e.g. n-grams) would let typed terms use `filter[fulltext]` again and remove the merge logic. It needs a backend change.

## Addendum: the match strategy is configurable

When the search generalises into a reusable package, the strategy becomes a per-project setting: `native_contains` (this ADR, the default) or `index_fulltext` for a backend whose index matches partial words. The Search API index is required for search under either strategy.

## Update (2026-10-25)

The decision stands. The search code is now in the package and generic:

- `SearchFetcher` replaces `SearchFetchService`, with `SearchTabs`, `SearchSorts`, `SearchFilterSet` and `SearchQueries` in place of the earlier tab, sort and filter classes.
- The relevance weights are the `term_fields` and `related_term_fields` of the searchable content type, not constants in code.
- Tabs, filters, sorts, page size and the 60-second search cache are descriptor settings under `search` and the content types. See the [Search setup](../search-setup.md) page.

## Update (2.0.0)

The decision stands. Search now works across any number of searchable content types:

- Every searchable type, Primary or Facet, follows one matching path. The strategy is the global `search.match_strategy` unless the type sets its own `match_strategy`.
- The index lookup is per type, capped by `search.index_lookup_pages`.
- Tabs are the searchable types, in `search.tabs` order or descriptor order. A filter and a sort belong to the type that declares them and apply on its tab only; switching tabs drops what the new tab does not declare.
- Suggestions query every searchable type, with quotas keyed by type.

## Update (3.0.0)

The decision stands for typed terms, and the addendum's line that the Search API index is required is reversed: the index is no longer part of the package.

- `SearchBackend` plans each tab (`plan()`, `capabilities()`) and `SearchFetcher` sends the plans as one concurrent batch. `NativeSearchBackend` is the default binding; the `proteo/drupal-search-api` package wraps it with `extend()` and adds the index paths. `NativeQueries` replaces `SearchQueries` for the native halves of `equals`, `year_range` and `boolean`.
- A search with filters and no term is answered natively, with a real `page[offset]`. A native collection has no total, so counts carry an `exact` flag and read `N+` until `links.next` says the last page was seen. `Listing::total` is nullable.
- A typed term keeps the behaviour of this record: one request per path, merged and scored in PHP, capped at `search.native_limit` per request. The cap is detected from `links.next`, not from the count equalling the limit.
- A boolean without a native path is a validation error unless the bound backend declares `indexOnlyBooleans`. `index_fulltext`, `search.index_lookup_pages` and the other index keys are removed from core; they belong to the extension. See [Upgrading](../upgrading.md#_2-0-0-to-3-0-0).

