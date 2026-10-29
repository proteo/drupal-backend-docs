# Contributing

The Markdown sources live in `docs/`. The committed `public/` is their VitePress build, and it is what the package serves.

- No built file name contains an uppercase letter, every page source is built, and every ADR is linked from the ADR index (`tests/Docs/`).
- The committed `public/` matches a fresh build (the `docs` CI job).

Add an entry under Unreleased in `CHANGELOG.md` for every change.

## Commands

```bash
composer docs:dev     # live preview of the docs
composer docs:build   # clean install, build to public/
```

Run `composer docs:build` and commit the rebuilt `public/` in the same pull request. CI only checks the build and fails when `public/` is stale or contains untracked files. Node is pinned in `docs/.nvmrc`.

VitePress keeps a local cache in `docs/.vitepress/cache` (git-ignored). After you edit pages, a stale cache can change file hashes, so a build right after a commit may differ from what you committed. CI builds from a fresh checkout without the cache. Match it before you commit `public/`:

```bash
rm -rf docs/.vitepress/cache
composer docs:build
```

Check: `git status --short public/` shows only the files you meant to change. Run the two commands again; the second run must leave no new changes.
