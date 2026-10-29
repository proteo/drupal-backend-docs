# Search suggestions

Offer matching titles while the visitor types. The package serves a small JSON endpoint; you add the input and a few lines of JavaScript. Suggestions are a convenience: when they fail, the visitor can still submit the search form.

## Before you start

- [Search is set up](./search-setup.md) with `search.enabled` true. The endpoint answers an empty list otherwise.
- Tabs in `search.tabs`. Suggestions come from every searchable type, in tab order.

## The endpoint

`GET {routes.prefix}/search/suggest?q=dune`, route `search.suggest`. It returns a JSON list of at most `suggestions.limit` objects. Each type is held to its quota in `search.suggestions.quotas`; the places the declared quotas leave are split evenly between the types without one, and unused places go to the types that have more matches, in tab order:

```json
[
  { "type": "talk", "title": "Dune", "url": "/talk/dune", "subtitle": "Frank Herbert" },
  { "type": "topic", "title": "Dune Studies", "url": "/topic/dune-studies", "subtitle": "" }
]
```

| Field | Value |
|---|---|
| `type` | Content type key of the suggestion |
| `title` | The title, with HTML entities decoded |
| `url` | The public URL of the item |
| `subtitle` | The title of the first entry of the relation named by `suggest_subtitle`, or an empty string |

The answer has `Cache-Control: no-store`, and the request is rate limited by `limits.search_suggest`, 60 per minute and client by default. A missing `q`, a term shorter than `term.min` or any Drupal failure gives `[]`.

## Add it to a page

Wire an input to the endpoint. This snippet waits for a pause in typing, fetches the list and renders links:

<<< @/snippets/search/suggest.blade.php{blade}[resources/views/components/search-field.blade.php]

Check: type two letters in the field. A list of links appears after a short pause, and typing one letter shows none.

## How suggestions are chosen

For each tab the package asks the native collection for entries whose title contains the term, with the title and path only. The requests run together. No Lucene cleaning is applied, because native filters are not Lucene. The term is trimmed and cut to `term.max` characters.

Then it picks up to `suggestions.limit` entries:

1. Each type contributes up to its quota from `suggestions.quotas`, or up to the limit when it has none.
2. Places a type leaves unused go to the types that had more matches, in tab order.
3. Entries that fail the type's `conditions` are dropped.

## Settings

| Setting | Default | Effect |
|---|---|---|
| `search.suggestions.limit` | `10` | Most suggestions, and the request size per type |
| `search.suggestions.quotas` | `[]` | Most suggestions per content type key |
| `search.term.min` | `2` | Shortest query that gets suggestions |
| `search.term.max` | `100` | Characters of the query that are used |
| `suggest_subtitle` on a type | `null` | Relation whose first title is shown beside the suggestion |
| `limits.search_suggest` | `60` | Requests per minute and client IP |

Suggestions are never cached, so each keystroke that passes your debounce is one batch of requests to Drupal. All tab requests go to the default Source.

## Next steps

- Keep full results in [Search setup](./search-setup.md).
- Look up the settings in the [Configuration reference](./configuration-reference.md#search).
- Diagnose empty lists in [Troubleshooting](./troubleshooting.md).
