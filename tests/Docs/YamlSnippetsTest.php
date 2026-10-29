<?php

namespace Proteo\DrupalBackendDocs\Tests\Docs;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class YamlSnippetsTest extends TestCase
{
    public function test_the_workflow_snippets_are_valid_yaml(): void
    {
        $files = glob(DocsPages::root().'/snippets/validation/*.yml');

        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $this->assertIsArray(Yaml::parseFile($file), "Invalid YAML in {$file}.");
        }
    }
}
