# Backend setup

Prepare your Drupal site so the package can read it. The package only reads, so Drupal needs a few modules, content that matches your descriptor, and an optional webhook sender. `drupal:contract` lists the exact requirements and `drupal:validate` checks them.

## Before you start

- A Drupal site on version 11.3.x or later. Older versions are not supported.
- A [descriptor](./vocabulary.md#descriptor) with at least one content type. The requirements below come from it.
- The module versions are not specified: the package states module names only, so use the releases that match your Drupal version.

## 1. Enable the modules

| Module | Needed for | When |
|---|---|---|
| JSON:API (core) | Reading content | Always |
| Decoupled Router | Resolving a URL alias to a content item | Always |

Search and related content need no other module: they read the same native collections. The [Index extension](./index-extension.md) adds Search API modules for an indexed search.

Check: `https://drupal.example/jsonapi` answers with a JSON document that has a `links` object.

## 2. Allow anonymous reading

The package sends no credentials. Every content type, taxonomy vocabulary, media type and file that the descriptor reads must be readable by anonymous visitors, as in the Drupal interface. Drupal answers 403 for anything else, and the package treats 403 like 404.

Check: open `https://drupal.example/jsonapi/node/talk` in a private browser window and see talk data, not an error.

## 3. Generate the contract

The contract is a Markdown checklist built from your descriptor:

```bash
php artisan drupal:contract > drupal-contract.md
```

It has these sections:

| Section | Lists |
|---|---|
| Required modules | The modules from step 1 |
| Sources | The base URL, JSON:API path and webhook line of each Source |
| Content type `<key>` | Resource type, a field table, a relationship table, the `include` paths, the native search paths and the accepted sorts |
| Drupal changes | Hooks and the payload for the webhook |

Commit the file and review descriptor changes as diffs. It is a pure function of the descriptor, so it never contacts Drupal.

<details>
<summary>Contract for the talk example</summary>

<<< @/snippets/contract/conference.md{md}

</details>

## 4. Match your content model

For every content type, make sure Drupal has what the contract lists:

1. **Fields.** Each field `source` must be an attribute of the resource. A dotted source such as `description.value` reads a nested value.
2. **Relationships.** Each relation `via` must be a relationship of the resource, with the `include` path listed in the contract. Mark a relation `optional` if the package may skip it when it is missing.
3. **Native search.** With search enabled, the collection must accept the `CONTAINS` paths of `term_fields` and `related_term_fields`, every filter path, every `filterable` condition and the date path of a year range. A boolean needs a `native` path.
4. **Sorts.** The sorts must be accepted by the collection.

Check: `php artisan drupal:validate` reports no `FAIL`.

## 5. Add the webhook

The webhook is optional but keeps content current. Follow [Webhook and cache flushing](./webhook-and-cache-flushing.md). Until it exists, changes appear when the fresh window ends.

## Check the backend

```bash
php artisan drupal:validate
```

The command probes Drupal with cheap requests, one Source at a time. It skips every later check of a Source whose JSON:API root fails. Pass a Source name to check one Source, or `--json` for machine-readable output. Unknown Source names print an error and exit with 1.

| Order | Check | Result |
|---|---|---|
| 0 | Descriptor keys the package does not read, such as a typo | Warn, with the key |
| 1 | JSON:API root answers with a `links` object | Fail otherwise |
| 2 | The resource type appears in the root | Fail otherwise |
| 3 | One item of the collection loads with the declared includes | Warn when the collection is empty, fail on any error |
| 4 | Every field `source` is an attribute of that item | Fail when missing |
| 5 | Every relation `via` is a relationship | Fail when missing; warn when the relation is optional |
| 6 | Decoupled Router resolves a sample alias to the expected type | Warn without a sample, fail on error |
| 7 | `search.native_limit`, printed as configured | Pass; never probed |
| 8 | Each `CONTAINS` path of `term_fields` and `related_term_fields` is accepted by the collection | Fail when rejected |
| 9 | Each filter path, sort and `filterable` condition is accepted by the collection | Fail when rejected |
| 10 | Checks added by an installed extension, see below | As that check decides |
| 11 | A webhook secret is configured | Fail on the default Source, warn on any other Source |

Checks 7 to 9 run only when search is enabled, with a request of one item each. The command fails when any check fails, and warnings never fail it. It does not check `files_path`, image styles, or whether Drupal actually sends webhooks.

Without a Source argument, the command ends with an `extensions` scope: the bound `SearchBackend` and `RelatedStrategy` classes, the backend's capabilities, and how many checks and contract contributors are tagged. It also reports any descriptor rule the bound backend cannot satisfy, such as a boolean field without a `native` path. `--json` carries the same results. An extension tags its own checks with `drupal-backend.checks`; one that throws is reported as a failure.

<details>
<summary>Sample text output (abridged)</summary>

```text
source:default
  JSON:API root: expected https://drupal.example/jsonapi answers, found ok ........ PASS
  Webhook secret: expected a webhook secret, found not set ........................ FAIL
    Fix: Set `webhook.secret` on the Source and `cache_webhook.secret` in Drupal; contract section "Drupal changes".
```

</details>

The probes use fixed timeouts of 10 seconds for the request and 3 seconds to connect, not the `drupal.*` settings.

For scheduled and deploy-time runs see [Validation in CI/deploy](./validation-in-ci-deploy.md).

## Next steps

- Declare content in [Mapping content](./mapping-content.md).
- Send change notifications with [Webhook and cache flushing](./webhook-and-cache-flushing.md).
- Fix failing checks with [Troubleshooting](./troubleshooting.md).
