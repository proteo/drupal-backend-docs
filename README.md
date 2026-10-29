# proteo/drupal-backend-docs

Developer documentation for [`proteo/drupal-backend`](https://github.com/proteo/drupal-backend), served by your Laravel app at `/backend-docs`. The package ships a pre-built static site and has no dependency on `proteo/drupal-backend`.

## Install

```bash
composer require --dev proteo/drupal-backend-docs
```

The service provider is discovered automatically. Open `/backend-docs` in your app. With the default path there is nothing to publish or build.

## Configuration

If for some reason you need to publish the documentation outside a dev environment, or change the default public path, there are a few things you can control.

By default, the route answers outside production and answers 404 in production. To change that, publish the config:

```bash
php artisan vendor:publish --tag=drupal-backend-docs-config
```

`config/drupal-backend-docs.php`:

| Key | Default | Effect |
|---|---|---|
| `enabled` | `null` | `null` serves the docs outside production. `true` always serves them. `false` always answers 404. |
| `public_path` | `'backend-docs'` | The URL path the docs are served at. Lowercase letters, digits, `-` and `_`, nested with `/` (for example `internal/docs`). |

### Changing the path

The built pages contain their own path, so any value other than `backend-docs` needs a generated copy. After setting `public_path`, run:

```bash
php artisan drupal-backend-docs:publish
php artisan route:clear
```

The command rewrites the bundled build for the configured path into `storage/app/drupal-backend-docs`. Run it again after you change `public_path` or upgrade the package. Until then the route answers 404 and logs a reminder, because a mismatched copy would have broken links. Add the command to your deploy script, since `storage/` is usually not committed.

The route is named `drupal-backend-docs.docs`. If your app has its own `/backend-docs` route, change `public_path` or set `enabled` to `false`. After upgrading or changing `public_path`, run `php artisan route:clear` if you cache routes.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

GNU General Public License v3.0.
