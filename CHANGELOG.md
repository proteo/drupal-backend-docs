# Changelog

All notable changes to this package are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the package follows [Semantic Versioning](https://semver.org/).

## Unreleased

### Added

- `public_path` config option and the `drupal-backend-docs:publish` command to serve the documentation at a path other than `/backend-docs`. The default path needs no extra step.

## 1.0.0 - 2026-10-29

### Added

- The developer documentation for `proteo/drupal-backend`. Served at `/backend-docs` outside production (route `drupal-backend-docs.docs`; config `enabled` in `config/drupal-backend-docs.php`, publishable with the `drupal-backend-docs-config` tag).
- The site title is now "Drupal Backend".
