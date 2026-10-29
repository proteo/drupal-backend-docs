# Validation in CI/deploy

The package's own CI cannot reach your Drupal site, so the check runs in your project, against the real backend: when you deploy, and on a schedule that catches changes made in Drupal without a frontend deploy.

## Before you start

- A working `drupal:validate` run on your machine. See [Artisan commands](./artisan-commands.md#drupal-validate).
- The environment variables your descriptor reads, such as `DRUPAL_BASE_URL`, available to the pipeline.
- A backend that the pipeline's runner can reach over the network.

## What the command tells the pipeline

`php artisan drupal:validate --json` exits with 1 when any check fails and with 0 otherwise. Warnings never fail it. The JSON has `ok` and a list of `results` with `status`, `scope`, `check`, `expected`, `found` and `hint`. The `hint` names the section of `drupal:contract` that describes the fix.

`php artisan drupal:validate reviews` checks one Source. A name that is not declared exits with 1.

## On deploy

Run the checks after the new release is live and the cache is flushed, so a broken contract fails the pipeline instead of the first visitor. These steps also confirm that the committed contract still matches the descriptor:

<<< @/snippets/validation/deploy.yml{yaml}[.github/workflows/deploy.yml]

Check: a deliberately wrong `DRUPAL_BASE_URL` makes the step fail, and the artifact holds the JSON.

The steps use `shell: bash` so that a pipe into `tee` keeps the command's exit code. Keep `drupal-validate.json` as a build artifact. Failing here does not roll anything back by itself; decide in your pipeline whether the step blocks the release or only alerts.

## On a schedule

A nightly job catches drift: someone removes a field in Drupal, or the collection stops accepting a filter path. It needs your config and the Source variables, and no database as long as your app does not touch one while booting:

<<< @/snippets/validation/nightly.yml{yaml}[.github/workflows/drupal-drift.yml]

Send a failed run to the channel your team watches. A failure on a day without a deploy means Drupal changed.

## Keep the contract in version control

`php artisan drupal:contract > drupal-contract.md` prints the checklist your Drupal team works from. Commit it. The deploy step above compares it with a fresh print, so a descriptor change without an updated contract fails the build, and reviewers see requirement changes as diffs. Hand the file to the Drupal developers when it changes.

## Reading a failure

| Output | Likely cause | Where to look |
|---|---|---|
| `JSON:API root` fails | Wrong `base_url` or `jsonapi_path`, JSON:API disabled, network blocked | [Backend setup](./backend-setup.md#_1-enable-the-modules) |
| `Resource type` is not listed | The bundle is missing, or the type is not exposed | The `Content type` section of the contract |
| `Fields` or `Relationships` fail | Drupal lacks a field named in the descriptor | The tables in the contract |
| A `CONTAINS`, filter or `Sort` probe fails | The native collection does not accept the path | The `Native search` lines in the contract |
| An `extensions` line fails | A bound search backend cannot answer the descriptor, or an installed extension's check failed | The extension's own documentation |
| `Webhook secret` fails | No secret on the default Source | [Webhook and cache flushing](./webhook-and-cache-flushing.md) |

## Next steps

- Look up each command in [Artisan commands](./artisan-commands.md).
- Fix failures with [Troubleshooting](./troubleshooting.md).
- Test your pages without Drupal in [Testing](./testing.md).
