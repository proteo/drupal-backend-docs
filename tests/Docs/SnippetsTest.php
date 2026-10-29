<?php

namespace Proteo\DrupalBackendDocs\Tests\Docs;

use PHPUnit\Framework\TestCase;

class SnippetsTest extends TestCase
{
    public function test_every_php_snippet_is_valid_php(): void
    {
        $files = glob(DocsPages::root().'/snippets/{*,*/*}.php', GLOB_BRACE);

        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            exec(PHP_BINARY.' -l '.escapeshellarg($file).' 2>&1', $output, $status);

            $this->assertSame(0, $status, "Syntax error in {$file}: ".implode("\n", $output));
            $output = [];
        }
    }
}
