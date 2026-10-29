# Views and overrides

The package ships bare HTML views so a new project renders at once. You replace them by creating a view with the right name in your own `resources/views`. This page lists every view name and what each receives.

## Before you start

Know which routes render which views. See [Routes and controllers](./routes-and-controllers.md). The default views have no layout or styling; they exist to be replaced.

## How a view is chosen

Each controller and component picks the first view that exists in a list, ending with the package default. The lists, from highest priority:

| Page | Lookup order |
|---|---|
| Item page | `views.item` of the content type, `{key}.item`, `drupal::item` |
| Listing page | `views.index` of the content type, `{key}.index`, `drupal::index` |
| Static page | `pages.{slug}`, `drupal::page` |
| Search page | `search`, `drupal::search` |
| Listing rows | `livewire.{key}`, `drupal::livewire.listing` |
| Search component | `livewire.search`, `drupal::livewire.search` |
| Search result row | `livewire.search.{tab}`, `drupal::livewire.search-item` |
| Error page | `errors.{status}`, `drupal::error` |

`{key}` is the content type key, `{slug}` the page slug, `{tab}` a key from `search.tabs` and `{status}` 404, 502 or 503. To override, create a view with the earlier name. Nothing else needs registering.

You can also replace a package view by file name: place it at `resources/views/vendor/drupal/{name}.blade.php`, for example `vendor/drupal/item.blade.php` for `drupal::item`.

## Package views

Every view the package ships, with the data it receives:

| View | Used for | Data |
|---|---|---|
| `drupal::item` | Item page | `{key}`, `entity`, `type`, `seo` |
| `drupal::index` | Listing page | `type` |
| `drupal::page` | Static page | `{key}`, `entity`, `type`, `seo` |
| `drupal::search` | Search page | none |
| `drupal::error` | Error page | `status`, `message` |
| `drupal::livewire.listing` | Rows and pager of a listing | `type`, `items`, `pages` |
| `drupal::livewire.search` | The search form, tabs and results | `term`, `searched`, `failed`, `results`, `filterSet`, `tabs` and more |
| `drupal::livewire.search-item` | One search result | `item`, `type`, `highlight` |
| `drupal::components.related` | `<x-drupal::related>`: the list of similar items | `entities` (mixed types, best first), `entity` (the source) |
| `drupal::components.related-slot` | `<x-drupal::related>` when it has a slot | `slot`, `entities`, `entity`. Prints the slot; there is rarely a reason to override it. |
| `drupal::components.highlight` | `<x-drupal::highlight>` | `highlighted` |

`type` is the content type object. `items` are entities and `pages` holds the `prev` and `next` URLs when they exist.

## Override the item page

Create a view named after the content type. It receives the entity under the key and as `$entity`:

<<< @/snippets/views/talk/item.blade.php{blade}[resources/views/talk/item.blade.php]

Check: open a talk. The page shows your markup, and `php artisan view:cache` compiles without error.

## Host the listing

The package's `drupal::index` view renders only a heading. Override it to host the Livewire listing, which fetches and pages the items:

<<< @/snippets/views/talk/index.blade.php{blade}[resources/views/talk/index.blade.php]

Replace the rows by naming a view `livewire.{key}`:

<<< @/snippets/views/livewire/talk.blade.php{blade}[resources/views/livewire/talk.blade.php]

The listing component narrows to related items with `relation` and `uuid` attributes, for example the talks of an speaker. It always needs a single root element, as every Livewire view does.

## Override the error page

The same view serves every failure of that status. The package passes `status` and `message`, and the message is always generic:

<<< @/snippets/views/errors/404.blade.php{blade}[resources/views/errors/404.blade.php]

Create `errors/502.blade.php` and `errors/503.blade.php` the same way. Detail about the upstream failure reaches only the logs.

## Use a layout

Wrap the content in your own layout as in any Laravel app:

```blade [resources/views/talk/item.blade.php]
<x-layouts.site :seo="$seo">
    <h1>{{ $talk->title }}</h1>
</x-layouts.site>
```

`$seo` is the page metadata. It is an `SEOData` object when `ralphjsmit/laravel-seo` is installed and the type has a `seo` map, or an `Entity` subclass implementing `ProvidesSeo` supplies it. Otherwise it is `null`.

## Blade components

| Component | Renders |
|---|---|
| `<x-drupal::related :entity="$talk" :criteria="['topics']"/>` | Items that share the criteria with the entity, or nothing when there are none or Drupal fails. Props: `entity`, `criteria`, `types`, `limit`, `order`. A slot replaces the default list and reads the items with `$component->entities()`. See [Related content](./related-content.md). |
| `<x-drupal::highlight :text="$title" :terms="$highlight"/>` | The text with the search terms marked. |
| `<livewire:drupal::listing content-type="talk"/>` | A paginated listing of a content type. |
| `<livewire:drupal::search/>` | The search form and results. See [Search setup](./search-setup.md). |

## Next steps

- Add similar items with [Related content](./related-content.md).
- Style the search page in [Search setup](./search-setup.md).
- Test the pages with the helpers in [Testing](./testing.md).
