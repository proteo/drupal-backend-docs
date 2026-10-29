# Entity decorators

Add values to entities that Drupal does not store, such as an average rating from another service, computed once for a whole page instead of once per row.

## Before you start

- A content type to decorate, declared in the [descriptor](./descriptor-reference.md).
- A class implementing `Proteo\DrupalFront\Content\EntityDecorator`.

## 1. Write the decorator

The package calls `decorate()` once per type and response with every entity of that type, nested ones included and each once. Return the extras by entity id:

<<< @/snippets/fixtures/TalkDecorator.php{php}[app/Content/TalkDecorator.php]

## 2. Name it in the descriptor

Set `decorator` on the content type. The class is resolved from the container, so it may take dependencies:

```php
'talk' => [
    // ...
    'decorator' => App\Content\TalkDecorator::class,
],
```

## 3. Read the extra in a view

```blade
{{ $talk->extra('rating') }}
```

`extra()` returns `null` for an entity the decorator did not mention and throws for a type with no decorator. `hasExtra()` tells them apart. An extra is not a field: it is never mapped from Drupal and is not part of `get()`.

Check: render a talk and see the rating; a type without `decorator` throws a clear error when a view asks for an extra.

## What to know

- The decorator runs on item, listing, search and related responses. It does not run on suggestions.
- Nothing is cached by the package. A decorator that calls a remote service must cache that call itself.
- An exception thrown by a decorator propagates; the page fails like any other error.
- `DescriptorSmokeTests` runs each decorator on faked entities, and `FakesDrupal::fakeExtras()` binds a stand-in in your tests.
- `drupal:validate` checks that the class exists and implements the interface.

## Next steps

- Declare the type in the [Descriptor reference](./descriptor-reference.md).
- Fake Drupal in tests with [Testing](./testing.md).
