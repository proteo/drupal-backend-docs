<?php

namespace Proteo\DrupalBackendDocs;

use InvalidArgumentException;

/**
 * Resolves where the documentation is served from and which build backs it.
 *
 * The committed `public/` is built for {@see self::DEFAULT_PATH}. Any other
 * public path needs a copy rewritten by `drupal-backend-docs:publish`.
 */
class DocsSite
{
    public const string DEFAULT_PATH = 'backend-docs';

    public static function publicPath(): string
    {
        return self::normalise((string) config('drupal-backend-docs.public_path', self::DEFAULT_PATH));
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function normalise(string $path): string
    {
        $path = trim($path, '/');

        if ($path === '' || ! preg_match('#^[a-z0-9_-]+(/[a-z0-9_-]+)*$#', $path)) {
            throw new InvalidArgumentException(
                "Invalid drupal-backend-docs.public_path [{$path}]: use lowercase letters, digits, `-` and `_`, optionally nested with `/`."
            );
        }

        return $path;
    }

    public static function packageRoot(): string
    {
        return realpath(__DIR__.'/../public') ?: __DIR__.'/../public';
    }

    public static function publishedRoot(): string
    {
        return storage_path('app/drupal-backend-docs');
    }

    /**
     * Identifies the committed build: its entry page names every hashed asset.
     */
    public static function buildHash(): string
    {
        return md5_file(self::packageRoot().'/index.html') ?: '';
    }

    /**
     * The directory to serve, or null when the published copy is missing or stale.
     */
    public static function root(): ?string
    {
        $path = self::publicPath();

        if ($path === self::DEFAULT_PATH) {
            return self::packageRoot();
        }

        $manifest = @json_decode((string) @file_get_contents(self::publishedRoot().'/manifest.json'), true);
        $root = realpath(self::publishedRoot().'/site');

        if ($root === false || ! is_array($manifest)
            || ($manifest['public_path'] ?? null) !== $path
            || ($manifest['build'] ?? null) !== self::buildHash()) {
            return null;
        }

        return $root;
    }
}
