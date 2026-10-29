<?php

namespace Proteo\DrupalBackendDocs\Tests\Docs;

use PHPUnit\Framework\TestCase;

class BuiltSiteTest extends TestCase
{
    public function test_no_built_file_name_contains_an_uppercase_letter(): void
    {
        $files = DocsPages::builtFiles();

        $this->assertNotEmpty($files, 'Run `composer docs:build` and commit public/.');

        $this->assertSame([], array_values(array_filter($files, fn (string $f): bool => $f !== strtolower($f))));
    }

    public function test_every_page_source_is_built(): void
    {
        $built = DocsPages::builtFiles();

        foreach (array_keys(DocsPages::all()) as $slug) {
            $html = $slug === 'index' ? 'index.html' : $slug.'.html';

            $this->assertContains($html, $built, "Page [{$slug}] is missing from public/.");
        }
    }
}
