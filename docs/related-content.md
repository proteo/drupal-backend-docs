# Related content

Show talks similar to the one a visitor is reading. The `<x-drupal::related>` component asks the `RelatedContent` service for entries that share topics, tracks or speakers with the current item, ranks them and renders a list. You say what to compare at the call site, and you can replace the ranking with your own class.

## Before you start

- Content types that point at the same kind of entity: a `talk` and a `workshop` that both relate to `topic` can be compared through it. See [Relations](./descriptor-reference.md#relations).
- Each relation you compare is declared on the source type and on every type you look in, and the source's relation has a `via` that Drupal accepts as a filter path (`topic.id`).
- Related content works with `search.enabled` left off.

## 1. Pass the criteria

A criterion is a relation of the item's content type. List them in priority order and render the component:

<<< @/snippets/related/related.blade.php{blade}[resources/views/talk/item.blade.php]

| Prop | Default | Effect |
|---|---|---|
| `:entity` | required | The item to find neighbours of. |
| `:criteria` | required | Relation names in priority order. A `Criterion` object names a different relation per target type. |
| `:types` | the entity's own type | Content type keys to look in. Each must reach the same kind of entity through its relation. |
| `:limit` | `10` | Most items returned. |
| `:order` | the type's default sort | Attribute that breaks ties, `-created` for newest first. It must be a field of every target type. |

Check: open a talk that shares a topic with others. A list of links appears. A talk with nothing in common renders nothing, not an empty heading.

## 2. Write your own markup

Without a slot the component prints a plain list of links. Put your markup in the slot to style a listing your way; different pages can render the same component differently. The slot renders once, only when there are results, and reads them through `$component->entities()`, a collection of entities, best match first:

<<< @/snippets/related/related-slot.blade.php{blade}[resources/views/talk/item.blade.php]

`$component->entities()` is a normal collection, so `->count()` and `->isEmpty()` work. A slot cannot be run once per item, which is why you write the `@foreach` yourself. Headings and wrappers inside the slot disappear with the list when nothing matches. To change the default markup for every listing instead, override `drupal::components.related`, see [Views and overrides](./views-and-overrides.md#package-views).

Check: the page shows your markup with one entry per related talk. A talk with nothing in common renders nothing, heading included.

## 3. Look across types

Pass `:types` to mix content types in one list. The relation is matched by name, so `topics` on a talk must also exist on a workshop, or map it with a `Criterion`:

```blade
<x-drupal::related
    :entity="$talk"
    :criteria="[new \Proteo\DrupalFront\Content\Related\Criterion('topics', ['workshop' => 'subjects'])]"
    :types="['talk', 'workshop']"
/>
```

Every relation must point to the same target type on every target, or the request throws `InvalidRelatedRequest`.

## How items are ranked

`NativeRelatedStrategy` sends one request per target type and criterion, asking Drupal for items whose relationship contains one of the source's ids (`filter[topic.id][operator]=IN`). Each request returns at most `search.native_limit` items. Then:

1. Count the entries each candidate shares with the current item, per criterion, from the relationship data already in the response.
2. Compare the counts as a list: the first criterion first, the next only to break a tie. With `['topics', 'tracks']`, a talk that shares one topic always beats a talk that shares three tracks.
3. Break remaining ties by `order`, then by the position of the type in `:types`, then by id.
4. Merge the candidates by id, drop the current item and keep the first `limit`.

Requests go through the search cache path (60 seconds, keyed by the content generation), and an item that `conditions` hide is left out. One request per type and criterion is sent, so a request is rejected when `types × criteria` is above `drupal.related.max_requests` (12).

A criterion for which the source has no values contributes nothing; if none has values, the result is an empty list, not an error.

The native pool is cut at `native_limit` per request. On a site where one topic is shared by many hundreds of items, the best match may fall outside it. The [Index extension](./index-extension.md) ranks from a single indexed query instead.

## Failures

| Situation | Outcome |
|---|---|
| The request cannot be answered whatever the backend does: an unknown type or relation, relations that point to different kinds of entity, an `order` attribute a target lacks, no criteria, a limit below 1, too many requests | `InvalidRelatedRequest`, a `LogicException`. It is a configuration error, so it is not caught and the page fails. |
| Drupal is unavailable or answers badly | The service logs a warning and returns an empty collection. The component renders nothing. |

The component catches only `DrupalException`. Call the service yourself, with the same rules:

```php
use Proteo\DrupalFront\Content\Related\Criterion;
use Proteo\DrupalFront\Content\Related\RelatedContent;
use Proteo\DrupalFront\Content\Related\RelatedRequest;

$items = app(RelatedContent::class)->find(
    new RelatedRequest($talk, [new Criterion('topics'), new Criterion('tracks')], limit: 6, order: '-created'),
);
```

`RelatedRequest::$extras` carries hints for a strategy; the native one ignores them.

## Replace the ranking

`RelatedContent` asks a `RelatedStrategy` for the entities. Write your own and bind it to replace the default. This one returns the newest talks:

<<< @/snippets/related/LatestTalksStrategy.php{php}[app/Content/Related/LatestTalksStrategy.php]

<<< @/snippets/related/AppServiceProvider.php{php}[app/Providers/AppServiceProvider.php]

The strategy receives a request that is already validated. Let a `DrupalException` propagate; the service turns it into an empty list. An extension package such as [Index extension](./index-extension.md) wraps the binding with `extend()` instead of replacing it.

## When nothing shows

- The component renders nothing when the strategy returns no items or Drupal fails; a failure is also logged as a warning.
- The page fails with `InvalidRelatedRequest` when the call does not fit the descriptor. The message names the cause.

Common causes of an empty list are a source with no values for the criteria, or no other item sharing them. See [Troubleshooting](./troubleshooting.md).

## Next steps

- Place the component in your [item view](./views-and-overrides.md#override-the-item-page).
- Declare relations in the [Descriptor reference](./descriptor-reference.md#relations).
- Set up search itself in [Search setup](./search-setup.md).
