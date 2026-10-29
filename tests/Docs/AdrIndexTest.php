<?php

namespace Proteo\DrupalBackendDocs\Tests\Docs;

use PHPUnit\Framework\TestCase;

class AdrIndexTest extends TestCase
{
    public function test_every_adr_is_linked_from_the_index(): void
    {
        $index = DocsPages::get('adr/index');

        foreach (array_keys(DocsPages::all()) as $slug) {
            if (str_starts_with($slug, 'adr/') && $slug !== 'adr/index') {
                $this->assertStringContainsString('(./'.substr($slug, 4).'.md)', $index, "ADR [{$slug}] is not linked from the ADR index.");
            }
        }
    }
}
