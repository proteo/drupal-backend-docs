<?php

namespace Proteo\DrupalBackendDocs\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use Proteo\DrupalBackendDocs\DocsSite;
use RuntimeException;

class PublishCommand extends Command
{
    protected $signature = 'drupal-backend-docs:publish';

    protected $description = 'Generate the documentation for the configured public_path';

    private const array TEXT_EXTENSIONS = ['html', 'js', 'mjs', 'json', 'css', 'xml', 'txt'];

    public function handle(Filesystem $files): int
    {
        try {
            $path = DocsSite::publicPath();
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($path === DocsSite::DEFAULT_PATH) {
            $files->deleteDirectory(DocsSite::publishedRoot());
            $this->info('public_path is the default; the bundled build is served as is. Nothing to generate.');

            return self::SUCCESS;
        }

        $target = DocsSite::publishedRoot();
        $temp = $target.'.tmp';
        $files->deleteDirectory($temp);
        $files->ensureDirectoryExists($temp);

        try {
            $this->copyRewritten(DocsSite::packageRoot(), $temp.'/site', $path);
            $files->put($temp.'/manifest.json', json_encode(['public_path' => $path, 'build' => DocsSite::buildHash()]));
        } catch (RuntimeException $e) {
            $files->deleteDirectory($temp);
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $files->deleteDirectory($target);
        $files->moveDirectory($temp, $target);

        $this->info('Documentation generated for /'.$path.'. Run `php artisan route:clear` if you cache routes.');

        return self::SUCCESS;
    }

    private function copyRewritten(string $from, string $to, string $path): void
    {
        mkdir($to, 0755, true);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($from, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            $relative = substr($item->getPathname(), strlen($from) + 1);
            $destination = $to.'/'.$relative;

            if ($item->isDir()) {
                mkdir($destination, 0755, true);

                continue;
            }

            if (! in_array(strtolower($item->getExtension()), self::TEXT_EXTENSIONS, true)) {
                copy($item->getPathname(), $destination);

                continue;
            }

            $count = 0;
            $content = preg_replace(
                '#(?<![A-Za-z0-9_-])/'.preg_quote(DocsSite::DEFAULT_PATH, '#').'(?![A-Za-z0-9_-])#',
                '/'.$path,
                (string) file_get_contents($item->getPathname()),
                -1,
                $count,
            );

            if ($relative === 'index.html' && $count === 0) {
                throw new RuntimeException('No /'.DocsSite::DEFAULT_PATH.' references found in index.html; the build changed and cannot be rewritten.');
            }

            file_put_contents($destination, $content);
        }
    }
}
