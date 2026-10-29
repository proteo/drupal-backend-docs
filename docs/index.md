# Introduction

`proteo/drupal-backend` turns a Drupal site into the content backend of a Laravel app. You describe your Drupal content once, in a PHP file called the [descriptor](./vocabulary.md#descriptor), and the package fetches, caches, maps and routes it for you.

## Who it is for

You are a Laravel developer building a frontend for content that editors manage in Drupal. You want typed objects in your Blade views, not raw JSON:API documents, and you do not want to write a fetcher, a cache and a webhook for every content type.

## What you get

- **Content pages without controllers.** Each [content type](./vocabulary.md#content-type) in the descriptor gets a listing route, an item route and a default view. You override the views, not the plumbing.
- **A read model.** Views receive an [Entity](./vocabulary.md#entity) whose properties carry the names you chose, not Drupal's field names.
- **A cache that stays current.** Responses are cached with a stale-while-revalidate window, and a signed [webhook](./vocabulary.md#webhook) from Drupal replaces every cached page with fresh content on the next request. See the [caching model](./caching-model.md).
- **Search.** An optional Livewire search page with tabs, filters, sorting and suggestions, answered from native JSON:API collections. An optional [extension](./index-extension.md) adds Search API index support.
- **Several backends.** Content types can live on different Drupal sites ([Sources](./vocabulary.md#source)), each with its own cache and webhook.
- **Checks and test helpers.** `drupal:contract` prints what your Drupal backend must provide, `drupal:validate` checks a live backend against it, and `FakesDrupal` builds JSON:API documents for your tests.

## What it does not do

- It does not write to Drupal. The package only reads.
- It does not ship a theme. The default views are bare HTML meant to be replaced.
- It does not replace Drupal modules. The Drupal side stays your responsibility: the package prints a checklist ([`drupal:contract`](./artisan-commands.md)) and validates the result.
- It does nothing until you declare a content type. With no content types the package registers no routes.

## Requirements

| Component | Version |
|---|---|
| PHP | 8.4 or later |
| Laravel | 13 |
| Livewire | 4.3 or later |
| Drupal | 11.3 or later, with core JSON:API and Decoupled Router |
| Indexed search (optional) | The [Index extension](./index-extension.md): Search API with a JSON:API Search API index |
| SEO metadata (optional) | `ralphjsmit/laravel-seo` |

Exact module versions are not specified. [Backend setup](./backend-setup.md) lists what the Drupal side needs.

## Next steps

1. Follow the [Quick start](./quick-start.md) to render one content type in about ten minutes.
2. Read the [Vocabulary](./vocabulary.md) so the terms in the rest of the documentation are familiar.
3. Plan your real project with [Installation](./installation.md) and [Architecture](./architecture.md).
