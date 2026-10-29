# Vocabulary

This page defines every term the documentation uses. Other pages link here on first use and never redefine a term. The package also ships a `CONTEXT.md` with the same vocabulary for coding agents; this page is the one written for you.

The examples use a conference site: talks and workshops, speakers, topics, tracks and slides. It is the running example everywhere in these docs.

## The descriptor

### Descriptor

The PHP array in `config/drupal-backend.php` that describes your Drupal content to the frontend: the [Sources](#source), the [content types](#content-type), and optional settings for [search](#searchable), routes, the webhook and rate limits. The package validates it when the app boots. See the [Descriptor reference](./descriptor-reference.md).

### Source

One Drupal backend connection: a base URL, a JSON:API path and an optional webhook secret. Every content type belongs to exactly one Source, and one Source is the default.

<<< @/snippets/fixtures/conference.php#sources{php}

### Content type

One Drupal entity type and bundle pair, such as `node` and `talk`, or `taxonomy_term` and `tracks`. Its key in the descriptor (`talk`) is what you use in code, route names and views. A Drupal vocabulary is a `taxonomy_term` bundle, so each vocabulary is its own content type.

<<< @/snippets/fixtures/conference.php#speaker{php}

### Role

How the package treats a content type.

The role follows from the Drupal entity type. `role` is written only to make a node a `page` or a `reference`.

| Role | Meaning | Example |
|---|---|---|
| Primary | An entity of type `node`, served in its own right with an item page and a listing. It is [searchable](#searchable) unless you configure it otherwise. | Talk, workshop, speaker |
| Facet | An entity of any other type, subordinate to nodes. It is not tied to one primary and has no count. It has a search tab only when you make it [searchable](#searchable). | Topic, track, slides, resource |
| `page` | A node served as a static item page with no listing. | About |
| `reference` | A node reached only through a field of another content type, possibly on another Source. It is optional and disappears quietly when unavailable. | Review |

A Facet whose Drupal entity has no path alias (for example a paragraph) gets no route unless it sets `url_prefix` or `listing_path`. Its `Entity::path` is `null` and `url()` throws a clear error.

### Field

A property of a content type, named by you and read from a Drupal field path (`source`). Views see the frontend name. A field has a type: `string`, `date`, `html`, `excerpt`, `integer` or `boolean`.

### Relation

A single or many-valued link from one content type to another, loaded through a JSON:API include path (`via`). A relation points at another content type or at an image ([Image relation](#image-relation)).

### Image relation

A relation that follows a media entity to its file. The result is an `Image` with its URL, alt text, dimensions, caption and image styles.

### File

A downloadable file behind a media or file entity, read with a `file` relation. The result is a `File` with its URL, name, MIME type and size; it is distinct from an [Image](#image-relation). It is `null` when the entity has no file.

### Condition

A rule the entries of a content type must satisfy to appear in lists, filter options, search and suggestions. It does not hide the item page. See [Conditions](./descriptor-reference.md#conditions).

### Decorator

A per-type class, named by `decorator`, that computes extra values for a whole batch of entities in one call. Each value is an extra, read with `$entity->extra('name')`; it is not a field. See [Entity decorators](./entity-decorators.md).

### Entity

The immutable object a view receives for one content item. Read fields by their frontend names (`$talk->title`). An unknown name throws instead of returning empty. Every entity also has `id`, `type`, `path` and `url()`, and any [extra](#decorator) a decorator added. You can supply your own subclass for a content type to add presentation helpers.

### URL prefix

The path segment the frontend serves a content type under. It defaults to the content type key, and an empty prefix serves the type at the site root.

### Alias prefix

What the package prepends to a URL's alias before asking Drupal to resolve it. It defaults to the content type key and may be empty. It is independent of the [URL prefix](#url-prefix).

### Alias

The Drupal path alias in the URL. It is the identity of a content item: the package looks the alias up in Drupal and ignores other URL segments.

## Search

### Native collection

The ordinary JSON:API collection for one content type. It matches partial words with `CONTAINS`, but has no relevance score and no total count.

### Searchable

Whether a content type has a search tab and suggestions. A primary is searchable by default and a facet is not; a page or reference never is. A primary with `searchable => false` keeps its routes, listing and item pages.

### Filter

A way to narrow a search, declared on a searchable content type and applied on that type's tab only. The package has three filter types: `equals` (one related item), `year_range` (a year of a date) and `boolean`.

### Boolean filter

Selects items whose boolean field is true, alone or combined by `any` or `all`. Each field is one checkbox and one URL parameter; only checked boxes filter. A field with `'present' => true` selects items where its path has a value instead. See [Search setup](./search-setup.md#_5-using-boolean-fields-as-checkbox-filters).

### Search backend

The class bound to `SearchBackend` that plans how each tab of a search is answered. The default, `NativeSearchBackend`, uses native collections; an extension can wrap it. See [Architecture](./architecture.md#where-you-can-step-in).

### Capabilities

What a search backend declares it can do: exact counts, boolean filters that exist only in an index, and fulltext matching. The validator and the contract read them; views read each count's own `exact` flag.

### Exact count

A tab count that is the true number of matches. A count the package cannot confirm, such as matches cut at `native_limit` or a page with more to come, is inexact and reads `N+`.

### Related request

What to relate to an entity: the source entity, [criteria](#criterion) in priority order, the content types to look in, a limit and a tie-break order. `RelatedContent` validates it and passes it to the bound `RelatedStrategy`.

### Criterion

One thing two entries can share, a relation of the source's content type. Sharing more entries of the first criterion outranks any number of the next.

### Sort

A named upstream sort order offered on the search page, such as `newest`.

### Tab

One group of search results: a searchable content type. Each tab shows its own count and is paginated separately. The first tab in `search.tabs` is the default.

## Cache

### Content generation

A counter that is part of every cache key. Replacing it invalidates all cached content of a Source at once, without deleting anything. Each Source has its own generation. See the [caching model](./caching-model.md).

### Fresh and stale window

A cached response is served as is while it is fresh (5 minutes by default). For a further 24 hours it is served stale while a refresh runs, and when Drupal fails the stale copy keeps being served.

### Negative cache

A short memory (60 seconds by default) of an alias that Drupal reported as unknown, so repeated requests for it do not reach Drupal.

### Webhook

The signed POST request Drupal sends when content changes. The package verifies it and starts a new content generation for the Source. See [Webhook and cache flushing](./webhook-and-cache-flushing.md).

### Contract

The checklist `drupal:contract` prints from your descriptor: the modules, fields, relationships, native search paths and hooks your Drupal backend must provide. `drupal:validate` checks a live backend against it.

## Failures

The package turns every problem with the backend into one of three outcomes. Visitors always see a generic message; detail goes to the logs.

| Outcome | HTTP status | Causes |
|---|---|---|
| Not found | 404 | Drupal answers 404 or 403, the alias does not resolve, or it resolves to a different content type. |
| Unavailable | 503 with `Retry-After: 30` | Connection error, timeout, 5xx after retries, or 429. |
| Bad response | 502 | The payload is invalid or a required field is missing. |

## Next steps

- Put the terms to work in the [Quick start](./quick-start.md).
- See every option of the descriptor in the [Descriptor reference](./descriptor-reference.md).
