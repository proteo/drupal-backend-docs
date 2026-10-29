# Artisan commands

The package adds three commands. `drupal:flush` clears cached content, `drupal:contract` prints what your Drupal backend must provide, and `drupal:validate` checks a live backend against that list. They are registered when the app runs in the console.

## Before you start

[Install the package](./installation.md) and declare a Source and at least one content type. The commands read your descriptor, and `drupal:validate` and `drupal:flush` need it to name a Source.

## Summary

| Command | Purpose | Contacts Drupal | Exit code 1 when |
|---|---|---|---|
| `drupal:flush {source?}` | Start a new [content generation](./vocabulary.md#content-generation) | No | The Source is unknown |
| `drupal:contract` | Print the backend checklist as Markdown | No | Never |
| `drupal:validate {source?} {--json}` | Check a live backend | Yes | A check fails or the Source is unknown |

## drupal:flush

Discards cached content so the next requests fetch fresh copies. Nothing is deleted: the command moves each Source to a new generation, and old entries expire on their own. See the [Caching model](./caching-model.md).

```bash
php artisan drupal:flush            # every Source
php artisan drupal:flush reviews    # one Source and the Sources it also flushes
```

| Argument | Required | Effect |
|---|---|---|
| `source` | No | Flush only this Source, and the Sources listed in its `flush.also.sources`. Without it, every Source is flushed. |

Output on success: `Drupal content cache flushed [default, reviews].` An unknown name prints `Unknown source [nope].` and exits with 1.

Run it after a deploy that changes the descriptor `includes`, and after changes made in Drupal without a webhook.

## drupal:contract

Prints the checklist of what Drupal must provide for your descriptor: modules, Sources, fields, relationships, include paths, native search paths, sorts and the webhook, plus any lines an installed extension adds. It reads only the descriptor, so it needs no network and always exits with 0.

```bash
php artisan drupal:contract > drupal-contract.md
```

It takes no arguments or options. Commit the file and review descriptor changes as a diff. [Backend setup](./backend-setup.md#_3-generate-the-contract) describes each section.

## drupal:validate

Checks a live Drupal backend against the descriptor, one Source at a time, cheapest check first. The list of checks is in [Backend setup](./backend-setup.md#check-the-backend).

```bash
php artisan drupal:validate
php artisan drupal:validate reviews
php artisan drupal:validate --json
```

| Argument or option | Effect |
|---|---|
| `source` | Check only this Source. An unknown name prints the declared Sources and exits with 1, so a typo cannot pass silently. |
| `--json` | Print the results as JSON instead of text. |

Without a `source`, the output ends with an `extensions` scope that lists the bound `SearchBackend` and `RelatedStrategy`, the backend's capabilities and the number of tagged checks and contributors. The exit code is 1 when any check fails. Warnings, such as an empty collection or a missing webhook secret on a secondary Source, do not fail it. Descriptor keys the package does not read appear as warnings with a suggestion.

Text output groups results by scope. Each line is `check: expected …, found …` with a `PASS`, `WARN` or `FAIL` track, and failures add a `Fix:` line that names the contract section to read.

<details>
<summary>JSON output</summary>

```json
{
    "ok": false,
    "results": [
        {
            "status": "fail",
            "scope": "source:default",
            "check": "Webhook secret",
            "expected": "a webhook secret",
            "found": "not set",
            "hint": "Set `webhook.secret` on the Source and `cache_webhook.secret` in Drupal; contract section \"Drupal changes\"."
        }
    ]
}
```

`status` is `pass`, `warn` or `fail`. `hint` is `null` for a passing check. The example is shortened to one result.

</details>

Run it in your pipeline: see [Validation in CI/deploy](./validation-in-ci-deploy.md).

## Next steps

- Run the checks automatically with [Validation in CI/deploy](./validation-in-ci-deploy.md).
- Understand what a flush does in the [Caching model](./caching-model.md).
- Prepare Drupal with [Backend setup](./backend-setup.md).
