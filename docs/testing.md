# Testing

Test your app against a faked Drupal backend. The package ships two helpers: `FakesDrupal` builds JSON:API documents shaped by your own descriptor, and `DescriptorSmokeTests` generates tests that check the descriptor and every page it implies. A search extension ships its own helper on top of `FakesDrupal`. Nothing in a test needs a running Drupal.

## Before you start

- The package is [installed](./installation.md) and a content type is declared. The [Quick start](./quick-start.md) shows the first test.
- Your test case extends Laravel's `Tests\TestCase`.

## Prepare the test environment

The cache would serve one test's response to the next. Turn it off for tests:

```xml [phpunit.xml]
<env name="DRUPAL_CACHE_ENABLED" value="false"/>
```

Consider `Http::preventStrayRequests()` in your base test case, so a request you forgot to fake fails loudly instead of reaching a real site.

## Fake Drupal with FakesDrupal

Add the trait to a test class. Every builder works from the descriptor in `config('drupal-backend')`:

| Method | Returns |
|---|---|
| `fakeEntity($type, $values, $related, $attributes)` | One entity as a `[resource, included]` pair |
| `fakeItem($entity)` | The document Drupal answers for a single item |
| `fakeCollection($type, $items, $hasMore)` | A native collection of entities, or a count to generate |
| `fakeAlias($type, $uuid)` | The Decoupled Router answer that resolves an alias to an entity |

`fakeEntity()` gives every field a sample value and every relation one related resource. You override what the test cares about:

- `$values` sets fields by their frontend names, plus `id`, `path` and `count`. A `null` leaves the attribute empty.
- `$related` sets relations by name: a list of `fakeEntity()` pairs for content relations, a list of URLs for links, and `true`, `null` or an array (`uri`, `alt`, `width`, `height`, `caption`, `styles`) for images.
- `$attributes` sets raw JSON:API attributes last.

A name the content type does not declare throws `InvalidArgumentException`, so a typo fails the test.

A page needs two responses, the alias lookup and the item. Answer them from one `Http::fake()` callback, as the snippet below does in `fakeTalk()`.

## Test pages and failures

This feature test covers a page, an unknown item, an unreachable backend and an invalid answer. It runs against the Quick start descriptor:

<<< @/snippets/testing/TalkFeatureTest.php{php}[tests/Feature/TalkFeatureTest.php]

The status codes follow the [failure outcomes](./vocabulary.md#failures): not found 404, unavailable 503 with `Retry-After`, bad response 502. A `required` field set to `null` is the quickest way to produce a bad response.

Check: `php artisan test --filter=TalkFeatureTest` passes with no Drupal running.

## Check the whole descriptor

`DescriptorSmokeTests` includes `FakesDrupal` and generates tests from your real descriptor. Add it to one test class and the tests exist for every content type:

<<< @/snippets/testing/DrupalSmokeTest.php{php}[tests/Feature/DrupalSmokeTest.php]

| Generated test | Checks |
|---|---|
| `test_the_descriptor_passes_offline_validation` | The descriptor has no rule violations. Unknown keys print a `WARNING` to standard error and do not fail the test. |
| `test_item_pages_render_their_entity` | Each primary and facet item page answers 200 and passes the entity to its view. |
| `test_listing_pages_render` | Each listing page answers 200. |
| `test_the_search_page_renders` | The search page answers 200. |
| `test_the_related_component_renders_for_each_type_with_a_content_relation` | The `<x-drupal::related>` wrapper renders for each type that has a content relation. |

The page tests skip, with a reason, while the routes they exercise are not registered, and the search test skips while search is disabled. Override `descriptorConfig()` to test another descriptor than `config('drupal-backend')`.

The smoke tests prove the descriptor and the default routes. They do not know your views, so keep your own feature tests for markup.

## Test your own strategy or component

Bind a double in the container and fake HTTP for the rest. `fakeCollection()` documents answer search and related content alike. See [Related content](./related-content.md) for a strategy that runs in a test. An installed extension documents its own helpers; the [Index extension](./index-extension.md) page links them.

## Next steps

- Check the real backend with [Validation in CI/deploy](./validation-in-ci-deploy.md).
- Look up the failure outcomes in [Troubleshooting](./troubleshooting.md).
- Learn how entities are built in [Mapping content](./mapping-content.md).
