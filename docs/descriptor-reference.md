# Descriptor reference

Every option of a content type: `content_types.<key>` and the fields, relations, filters, conditions and SEO settings inside it. Settings outside the content types are in the [Configuration reference](./configuration-reference.md).

## Before you start

Know the terms [content type](./vocabulary.md#content-type), [role](./vocabulary.md#role), [field](./vocabulary.md#field) and [relation](./vocabulary.md#relation). Tables list the key relative to its table (`conditions.*.attribute`), its type, the default and the effect.

A complete content type, from the talk example:

<<< @/snippets/fixtures/conference.php#talk{php}

## Content type options

The key under `content_types` (`talk`) names the type in code, route names and views.

| Key | Type | Default | Effect |
|---|---|---|---|
| `role` | string | required | `primary`, `facet`, `page` or `reference`. See [Roles](#roles). |
| `entity_type` | string | required | Drupal entity type, such as `node` or `taxonomy_term`. The resource type is `entity_type--bundle`. |
| `bundle` | string | required | Drupal bundle. |
| `source` | string | The default Source | Name of the [Source](./vocabulary.md#source) that serves this type. Must be declared. |
| `native_collection` | string | The bundle | Path below the JSON:API root used for listings and search, for example `track` or `node/article`. |
| `url_prefix` | string | The type key | Public path segment. An empty string serves the type at the site root. |
| `alias_prefix` | string | The type key | Prepended to the alias before Drupal resolves it, and removed again in public URLs. May be empty. |
| `listing_path` | ?string | `null` | Public listing path with a leading slash. The route is named `{key}.index`. Without it, the type has no listing. Unique across types. |
| `per_page` | ?int | `null` | Items per listing page. Required for a type that has a listing. |
| `default_sort` | ?string | `null` | Upstream `sort` of listings, such as `-created`. |
| `fields` | map | `[]` | Scalar values to read. See [Fields](#fields). |
| `relations` | map | `[]` | Links to other types, URLs and images. See [Relations](#relations). |
| `includes` | map | `[]` | Relation names to include per context: `item` for the item page, `listing` for listings and search rows. Image relations include their file. |
| `filters` | map | `[]` | Search filters. They apply on this type's tab only. See [Filters](#filters). |
| `sorts` | map | `[]` | Name to upstream sort, offered on the search page. The first is the default. `title` is always offered. |
| `searchable` | bool | `true` for `primary`, else `false` | Whether this type has a search tab and suggestions. Never true for a page or reference; a primary with `false` keeps its routes and pages. |
| `conditions` | list | `[]` | Rules the type's entries must satisfy to appear in listings, filter options, search and suggestions; all must hold. Item pages are not affected. |
| `title_attribute` | ?string | `null` | Name attribute used for options, suggestions and the title sort. Required for the target of an `equals` filter. When the title sort or relevance without a term needs a path, the order is `title_attribute`, then `sorts.title`, then the first `term_fields` path. |
| `term_fields` | map | title attribute at weight 1 | Drupal path to weight. The paths a typed term is matched against with `CONTAINS`. |
| `related_term_fields` | map | `[]` | Related paths to weight. Each is searched with its own request, and a match adds its weight. |
| `referenced_by` | map | `[]` | For references: holder type key to the field that holds this type's UUID. The holder must declare that field. |
| `optional` | bool | `true` for `reference`, else `false` | A bad response for an optional type is dropped and logged instead of failing the page. |
| `seo` | map | `null` | Page metadata. See [SEO settings](#seo-settings). |
| `views.item` | ?string | `null` | View for the item page. Wins over the default chain. See [Views and overrides](./views-and-overrides.md). |
| `views.index` | ?string | `null` | View for the listing page. |
| `entity` | ?string | `Entity` | Class that extends `Entity` to use for this type. |
| `decorator` | ?string | `null` | Class implementing `EntityDecorator`, resolved from the container. It runs once per response with the type's entities and returns extras by entity id, read with `$entity->extra($name)`. Nothing is cached. |
| `routes` | map | `[]` | Page type only: slug to URL with a leading slash. The route is named `page.{slug}`. Slugs are unique across pages. |
| `suggest_subtitle` | ?string | `null` | Content relation whose first entry's title appears beside a suggestion. |

## Roles

The role derives from `entity_type`: `node` is a primary, any other JSON:API entity type is a facet. `role` is written only to make a node a `page` or a `reference`; a role that contradicts the entity type fails validation. `bundle` is optional and defaults to `entity_type`.

| Role | Gets | Requires |
|---|---|---|
| `primary` | Item route, listing route, `filters`, `sorts` and a search tab (set `searchable` to `false` to opt out). | |
| `facet` | Item route and listing route when it has a path alias or sets `url_prefix` or `listing_path`; a search tab when `searchable` is `true`. | |
| `page` | Routes from its `routes` map only. Never searchable. | |
| `reference` | No routes. Reached through a field of another type, possibly on another Source. Never searchable. | `referenced_by` |

A type whose entity has no path alias (not a node or a term) has `Entity::path` equal to `null` and `url()` throws. There are no inverse relations: a primary lists the primaries related to it through the other type's `equals` filter on its relation.

## Fields

A field reads one Drupal attribute under the name you choose.

| Key | Type | Default | Effect |
|---|---|---|---|
| `source` | string | required | Attribute path on the resource. Dots reach into nested values, such as `description.value`. |
| `type` | string | `string` | `string`, `date`, `html`, `excerpt`, `integer` or `boolean`. See the table below. |
| `required` | bool | `false` | A missing or empty value is a bad response for the whole page. |
| `length` | ?int | `200` for `excerpt` | Excerpt length in characters. Only for `excerpt`. |

| Type | Result in the view |
|---|---|
| `string`, `date` | A non-empty string, or `null`. |
| `html` | Decoded HTML with links to Drupal and file paths rewritten. An empty string when empty. |
| `excerpt` | Plain text shortened to `length`. |
| `integer`, `boolean` | An int or a bool, or `null`. |

A required field returns its raw non-empty value whatever its type. Field and relation names must not be `id`, `type`, `path` or `url`, and a name cannot be both a field and a relation.

## Relations

| Key | Type | Default | Effect |
|---|---|---|---|
| `type` | string | `content` | `content` points at another content type and maps to `Entity`. `image` follows a media entity to an `Image`. `file` reads the file entity in `via` into a `File` (`url`, `name`, `mime`, `size`, `extension()`, `humanSize()`). |
| `via` | string | required | Drupal relationship name. It is also the include path. |
| `to` | ?string | `null` | Target content type key. Required for `content`, not allowed for `image` and `file`. |
| `many` | bool | `false` | `true` gives a list, `false` a single value or `null`. |
| `optional` | bool | `false` | A relation that fails to map is dropped and logged instead of failing the page. |
| `image_field` | ?string | `field_media_image` | File relationship on the media entity. Only for `image` relations. |

A `file` relation takes only `via`, holds one file and is `null` without a file or a `uri`. An `includes` entry may be dotted, such as `documents.file`: the relation, then a relation of its target type, expanded one level. An image needs a media entity, a file and a non-empty `uri`, otherwise it is `null`.

## Filters

Filters narrow search on the tab of the type that declares them. A key is the URL parameter, unique across all searchable types. These names are reserved and cannot be a filter key or checkbox parameter: `q`, `sort`, `tab`, `page`. While a filter is active, search shows only the tabs of the types whose filters are in use.

| Key | Type | Default | Effect |
|---|---|---|---|
| `type` | string | required | `equals`, `year_range` or `boolean`. See the table below. |
| `relation` | string | none | `equals` only: the content relation to filter by. Required. |
| `native_path` | ?string | `null` | Filter path on the native collection, such as `topic.id`. Required for `equals` and for `year_range` (the date path). |
| `first_year` | int | none | `year_range` only: earliest year offered. Required. |
| `fields` | map | none | `boolean` only: URL parameter to `['native' => path]`, the boolean path on the native collection, optionally with `'present' => true` when the path is a field or relationship rather than a boolean: the box then selects items where it has a value (`IS NOT NULL`). At least one. |
| `match` | string | `any` | `boolean` only: `any` selects items where at least one checked field is true, `all` where every checked field is. |
| `label` | ?string | The key as a headline | Label of the control. |
| `labels` | map | `[]` | `boolean` only: parameter to checkbox label. |

| Filter type | Value | Behaviour |
|---|---|---|
| `equals` | UUID of a related item | Choices come from the target type, up to 200 sorted by name. The target needs `title_attribute`. |
| `year_range` | Integer from `first_year` to the current year | Matches the year in UTC. The dropdown lists the newest year first. |
| `boolean` | One checkbox per field | Only a checked box filters. It selects items whose field is true, sent upstream as `1` on the native request. A field without a `native` path is an error that `drupal:validate` reports, unless the installed search backend can answer it (see the [Index extension](./index-extension.md)). |

For a worked boolean example, see [Using boolean fields as checkbox filters](./search-setup.md#_5-using-boolean-fields-as-checkbox-filters).

The set of filter types is closed: a new filter shape needs package code.

## Conditions

Each entry of `conditions` is one rule, for example `['attribute' => 'talk_count', 'operator' => '>', 'value' => 0]`. Rules are combined with AND and always combine with the visitor's filters.

| Key | Type | Default | Effect |
|---|---|---|---|
| `attribute` | string | required | Native field the rule reads. |
| `operator` | string | required | One of `=`, `<>`, `>`, `>=`, `<`, `<=`, `IN`, `NOT IN`, `IS NULL`, `IS NOT NULL`. |
| `value` | scalar or list | `null` | A scalar; a list for `IN` and `NOT IN`; none for `IS NULL` and `IS NOT NULL`. |
| `filterable` | bool | `false` | Send the native rule upstream as a filter. Otherwise it is tested in PHP after the page is fetched, so a page may come back short. |

## SEO settings

Each value names a field or relation of the type. See [Views and overrides](./views-and-overrides.md) for how the view receives `$seo`.

| Key | Type | Default | Effect |
|---|---|---|---|
| `title` | ?string | `null` | Field or relation that gives the page title. |
| `description` | string or map | `null` | A field name, or `['field' => ..., 'max' => 160]`. The result is a plain-text excerpt. |
| `image` | ?string | `null` | Field or relation for the image. Only plain strings are used. |
| `author` | ?string | `null` | Field or relation for the author of the page. Only plain strings are used. |

The package builds metadata when `ralphjsmit/laravel-seo` is installed. An `Entity` subclass that implements `ProvidesSeo` takes over completely.

## What validation checks

The package validates the descriptor when the app boots and reports every problem at once. The rules cover:

- Names: declared Sources, relation targets, `includes` and tabs must exist; built-in names are not allowed.
- Roles: filters only on primary types, `routes` only on pages, `referenced_by` on references.
- Uniqueness: listing paths, page slugs and filter URL parameters.
- Search: at least one searchable type, tabs that name searchable types once each, filter keys unique across types, a native path on every `equals` and `year_range` filter, a `native` path on every boolean field (unless the installed backend can answer index-only booleans), an `attribute` on every condition, and `term_fields` or a title attribute for every searchable type.

Outside production a problem stops the app. In production it is logged. See [Installation](./installation.md#behaviour-to-know-about).

## Next steps

- Turn a Drupal content model into a descriptor in [Mapping content](./mapping-content.md).
- Set up filters and tabs in [Search setup](./search-setup.md).
- See global options in the [Configuration reference](./configuration-reference.md).
