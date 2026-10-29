# Upgrading

What changes between releases and what to do about it. Each section covers one upgrade, newest first. The complete list of changes is in `CHANGELOG.md` in the package root, also at `vendor/proteo/drupal-backend/CHANGELOG.md`.

## Before you start

- Read the section for each version between yours and the target.
- Upgrade in a branch and run your test suite and `php artisan drupal:validate`.
- Only the latest release is documented here.

## Upgrade steps for every release

```bash
composer update proteo/drupal-backend
php artisan config:clear
php artisan route:clear
php artisan drupal:validate
```

Rebuild the config and route caches afterwards if your deploy uses them.

## 3.3.1 to 4.0.0

This release removes the documentation site from the package. Nothing else changes.

| Change | Effect | What to do |
|---|---|---|
| The `/frontend-docs` route and the `drupal-backend.docs` route name are gone | The package no longer serves this documentation. | Install `proteo/drupal-backend-docs` with `composer require --dev proteo/drupal-backend-docs` to read it at `/backend-docs`. |
| The `docs` key of `config/drupal-backend.php` is removed | `drupal:validate` warns about the unknown key `docs`. | Delete the key. The new package reads `enabled` from `config/drupal-backend-docs.php`. |

## 3.0.0 to 3.1.0

Nothing breaks. `<x-drupal::related>` accepts a slot, so each listing can print its own markup; without one it renders `drupal::components.related` as before. See [Related content](./related-content.md#2-write-your-own-markup). Run the steps above.

## 2.0.0 to 3.0.0

This release moves everything that needs a Search API index out of the package. Search and related content now run on native JSON:API alone, and a new package, `proteo/drupal-search-api`, brings the index back. It is a breaking release and the descriptor moves to `schema_version` 3.

A version 2 descriptor fails at boot with one error that lists every removed key still in use and its replacement. There are no shims. Fix each line, then run the steps above.

1. Decide whether you still want the index. Without it, search works on native collections with the limits in [Search setup](./search-setup.md#core-only-or-with-the-index-extension). If you keep it, install the extension before changing the descriptor: `composer require proteo/drupal-search-api`. Its [Upgrading page](./index-extension.md) maps each removed key to its new place.
2. Set `schema_version` to `3` and fix the listed keys, using the table below.
3. Replace every `related` entry with props on `<x-drupal::related>`.
4. Update tests that used `fakeIndex()`, `IndexRelatedStrategy` or `SearchQueries`.
5. Run the steps above and `php artisan drupal:validate`.

| Removed | Replace with |
|---|---|
| `schema_version` 2 | `schema_version` 3 |
| `search.index.*` (`source`, `path`, `bundle_field`, `fulltext_field`) | `index.*` in the extension's `config/drupal-search-api.php`. Without the extension, drop it. |
| `search.index_lookup_pages` | `index.lookup_pages` in the extension. |
| `search.match_strategy` and a type's `match_strategy` | The extension's `index.match_strategy` and `content_types.<key>.match_strategy`. Core matches terms natively. |
| `index_bundle` | `content_types.<key>.bundle` in the extension. |
| `index_fields` | Name the columns directly under `filters` in the extension. |
| `index_field` of a `year_range` filter | Give the filter a `native_path`; the column goes in the extension. |
| `index` of a `boolean` field | Keep `native`. A boolean without a `native` path is an error unless the extension is installed. |
| `index` of a condition | Drop it; the column goes under `conditions` in the extension. A condition must name an `attribute`. |
| `index_title_field` | Rename it `title_attribute`. |
| `related` on a content type | Drop it and pass props to `<x-drupal::related>`; see below. |

| Removed code | Replace with |
|---|---|
| `IndexRelatedStrategy`, `RelatedConfig`, `ContentType::related` | `RelatedContent`, `RelatedRequest`, `Criterion`; the extension supplies an index strategy. |
| `MatchStrategy`, `SearchQueries` | `NativeQueries` and the `SearchBackend` seam. |
| `Filter::indexField`, `BooleanField::index`, `Condition::index`, `ContentType::indexBundle`, `indexFields`, `indexTitleField`, `indexConditionQuery` | The extension's `IndexDescriptor`. |
| `FakesDrupal::fakeIndex()` | The extension's `FakesIndex` trait. `fakeEntities()` and `fakeDocument()` are now `protected`. |

The `related` setting becomes props:

```diff
-'related' => ['relations' => ['topics', 'tracks'], 'tie_break' => '-created', 'limit' => 6],
+<x-drupal::related :entity="$talk" :criteria="['topics', 'tracks']" order="-created" :limit="6"/>
```

`related.candidates` is gone: the native strategy takes at most `search.native_limit` items per request.

Other changes to review:

| Change | Effect | What to do |
|---|---|---|
| Filter-only searches are native | They page with a real offset and show `N+` until the last page. | Check views that print a total: `Listing::total` may be `null`; use `exact`. |
| `SearchBackend` and `RelatedStrategy` are the seams | An extension plugs in without touching the fetcher. | None, unless you replaced the fetcher. |
| `drupal:validate` | New native probes and an `extensions` scope; index probes leave core. | Update scripts that parse the check names. |
| `drupal.related.max_requests` | New shared setting, default 12. | None. |

Check: `php artisan drupal:validate` prints no `FAIL` and a search for a typed term and a filter-only search both return results.

## 1.1.0 to 2.0.0

This release removes the bookstore assumptions: the package now models any Drupal content. It is a breaking release and the descriptor moves to `schema_version` 2.

A version 1 descriptor fails at boot with one error that lists every removed key with its replacement. There are no shims. Fix each line, then run the steps above.

| Removed | Replace with |
|---|---|
| `schema_version` 1 | `schema_version` 2 |
| `facet_of` on a facet | Drop it. A Facet is any non-node entity type and is not tied to one primary. Relate the types with a relation and an `equals` filter. |
| `count_field`, the built-in `count` and `Entity::$count` | Declare the count as an ordinary field and hide empty entries with a [`conditions`](./descriptor-reference.md#conditions) rule. |
| The `any_flag` filter (`flags`, `group`) | A `boolean` filter with `fields` and `match`. See [Filters](./descriptor-reference.md#filters). |
| The `link` relation and the `Link` object | Model the related entity as a content type and reach it with a `content` relation; a paragraph without an alias is a facet with no routes. |
| `search.on_sale_pages`, `onSale()`, `hasFlags()`, `onlyOnSale()` | `search.index_lookup_pages` and the index lookup names. |
| `Descriptor::searchablePrimary()`, `SearchTabs::primary()`, `Descriptor::facetsOf()` | `Descriptor::searchableTypes()` and `SearchTabs::default()`. |
| "Only a primary has filters or is searchable", "exactly one searchable primary" | Filters, sorts and `searchable` belong to the type. Search needs at least one searchable type. |

Other changes to review:

| Change | Effect | What to do |
|---|---|---|
| Role follows the entity type | `node` is a primary and any other entity type is a facet. A facet is not searchable unless you set `searchable` to `true`. | Add `searchable => true` to each facet that had a search tab. |
| A facet without a path alias gets no route | Types such as a paragraph have `Entity::path` equal to `null` and `url()` throws. | Set `url_prefix` or `listing_path` to opt in. |
| Several searchable types | Each is a tab; `search.tabs` is optional. `match_strategy` can be set per type. | Review your tabs and the `search.suggestions.quotas`. |
| `term_fields` default | A searchable type with none matches its title attribute at weight 1. | None. |
| New: `decorator`, `file` relation, dotted includes | See [Entity decorators](./entity-decorators.md) and the [Descriptor reference](./descriptor-reference.md#relations). | Optional. |

Check: `php artisan drupal:validate` prints no `FAIL` and the search page shows a tab for each searchable type.

## 1.0.0 to 1.1.0

This release adds the documentation site and a few safety checks. Nothing needs to change for an app that already works, except the item marked **Action**.

| Change | Effect | What to do |
|---|---|---|
| Default `drupal.*` settings ship in the package | Timeouts, retries and cache windows have working defaults without a `config/drupal.php`. Your own file still wins, key by key. | Optional: delete the keys in your `config/drupal.php` that equal the defaults. See [Installation](./installation.md#3-publish-the-configuration). |
| Unknown keys produce warnings | A typo in `config/drupal-backend.php` shows in `drupal:validate` and in the descriptor smoke test, with a suggestion. Boot never fails because of it. | Run `php artisan drupal:validate` and fix the warnings. |
| `drupal:validate` with an unknown Source name exits 1 | It used to check nothing and exit 0. It now prints the declared Sources. | Update CI steps that pass a Source name. |
| `search.term.min` sets the message | The "enter at least N letters or numbers" text follows the setting. **Action:** `SearchForm::TOO_SHORT` is removed. | Replace it with `SearchForm::tooShortMessage()` in your views. |
| `drupal:contract` describes the webhook payload as `{"changes": [...]}` | Text only. The frontend still ignores the body. | Regenerate a committed `drupal-contract.md`. |
| A documentation route | Served outside production. Since 4.0.0 it lives at `/backend-docs` in the separate `proteo/drupal-backend-docs` package. | See [3.3.1 to 4.0.0](#_3-3-1-to-4-0-0). |
| Dist archives exclude the Markdown sources of `docs/` | The built site stays in `public/`. | None. |

<details>
<summary>The view change in detail</summary>

```diff
-{{ Proteo\DrupalFront\Livewire\Forms\SearchForm::TOO_SHORT }}
+{{ Proteo\DrupalFront\Livewire\Forms\SearchForm::tooShortMessage() }}
```

</details>

Check: the search page shows `Enter at least 2 letters or numbers.` for a one-letter term, and `php artisan drupal:validate` prints no `FAIL`.

## Next steps

- Look for problems in [Troubleshooting](./troubleshooting.md).
- Check the backend after upgrading with [Validation in CI/deploy](./validation-in-ci-deploy.md).
- Review every setting in the [Configuration reference](./configuration-reference.md).
