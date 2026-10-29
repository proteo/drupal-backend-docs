<?php

namespace Proteo\DrupalBackendDocs\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Proteo\DrupalBackendDocs\DocsSite;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Streams the committed documentation build from the package's `public/`.
 *
 * Global middleware (trusted proxies, maintenance mode, ...) still applies;
 * only the `web` group is skipped. The gate runs per request so it is safe
 * under `route:cache`.
 */
class DocsController extends Controller
{
    private const array MIME_TYPES = [
        'html' => 'text/html; charset=UTF-8',
        'css' => 'text/css; charset=UTF-8',
        'js' => 'text/javascript; charset=UTF-8',
        'mjs' => 'text/javascript; charset=UTF-8',
        'json' => 'application/json',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'ico' => 'image/x-icon',
        'woff2' => 'font/woff2',
        'woff' => 'font/woff',
        'txt' => 'text/plain; charset=UTF-8',
        'xml' => 'application/xml',
        'map' => 'application/json',
    ];

    public function __invoke(?string $path = null): BinaryFileResponse
    {
        abort_unless($this->enabled(), 404);

        $file = $this->resolve(trim((string) $path, '/'));

        abort_if($file === null, 404);

        $response = new BinaryFileResponse($file);
        $response->headers->set('Content-Type', self::MIME_TYPES[strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? 'application/octet-stream');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

    private function enabled(): bool
    {
        return config('drupal-backend-docs.enabled') ?? ! app()->isProduction();
    }

    /**
     * Tries `x`, `x.html` and `x/index.html`, and never leaves the build directory.
     */
    private function resolve(string $path): ?string
    {
        $root = DocsSite::root();

        if ($root === null) {
            Log::warning('drupal-backend-docs: public_path changed or the package was upgraded. Run `php artisan drupal-backend-docs:publish`.');

            return null;
        }

        if (str_contains($path, "\0") || str_contains($path, '\\')) {
            return null;
        }

        $candidates = $path === '' ? ['index.html'] : [$path, $path.'.html', $path.'/index.html'];

        foreach ($candidates as $candidate) {
            $real = realpath($root.'/'.$candidate);

            if ($real !== false && is_file($real) && str_starts_with($real, $root.DIRECTORY_SEPARATOR)) {
                return $real;
            }
        }

        return null;
    }
}
