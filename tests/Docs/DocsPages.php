<?php

namespace Proteo\DrupalBackendDocs\Tests\Docs;

use RuntimeException;

/**
 * Reads the Markdown sources under docs/ so tests/Docs checks share one loader.
 */
final class DocsPages
{
    public static function root(): string
    {
        return dirname(__DIR__, 2).'/docs';
    }

    public static function builtRoot(): string
    {
        return dirname(__DIR__, 2).'/public';
    }

    /**
     * Page sources keyed by slug ('quick-start', 'adr/0001-...'), excluding snippets and dependencies.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        $pages = [];
        $root = self::root();

        foreach (glob($root.'/{*,adr/*}.md', GLOB_BRACE) as $path) {
            $pages[substr($path, strlen($root) + 1, -3)] = (string) file_get_contents($path);
        }

        // Legacy guide, excluded from the build until its content folds into the new pages.
        unset($pages['validating-the-backend']);

        ksort($pages);

        return $pages;
    }

    public static function get(string $slug): string
    {
        return self::all()[$slug] ?? throw new RuntimeException("No docs page [{$slug}].");
    }

    /**
     * Rows of every Markdown table's first column on a page, with backticks stripped.
     *
     * @return list<string>
     */
    public static function tableKeys(string $slug): array
    {
        preg_match_all('/^\|\s*`([^`]+)`\s*\|/m', self::get($slug), $matches);

        return $matches[1];
    }

    /**
     * @return list<string> Paths of built files relative to public/.
     */
    public static function builtFiles(): array
    {
        $root = self::builtRoot();
        $files = [];

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = substr($file->getPathname(), strlen($root) + 1);
            }
        }

        sort($files);

        return $files;
    }
}
