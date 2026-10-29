<?php

namespace Proteo\DrupalBackendDocs\Tests\Docs;

use PHPUnit\Framework\TestCase;

class DiagramsTest extends TestCase
{
    public function test_diagrams_use_only_flowchart_or_sequence_diagram_and_stay_small(): void
    {
        foreach (DocsPages::all() as $slug => $markdown) {
            foreach ($this->diagrams($markdown) as $diagram) {
                $this->assertMatchesRegularExpression('/^(flowchart (LR|TD)|sequenceDiagram)\b/', ltrim($diagram), "Unsupported diagram type on [{$slug}].");
                $this->assertLessThanOrEqual(12, count($this->labelsOf($diagram)), "Diagram on [{$slug}] has more than 12 nodes.");
            }
        }
    }

    /**
     * @return list<string> Bodies of the fenced mermaid blocks.
     */
    private function diagrams(string $markdown): array
    {
        preg_match_all('/^```mermaid\n(.*?)^```/ms', $markdown, $matches);

        return $matches[1];
    }

    /**
     * Flowchart nodes written as id["Label"], id[Label], id{"Label"} or id("Label"), or the
     * display names of sequence diagram participants (`participant Id as Label`).
     *
     * @return list<string>
     */
    private function labelsOf(string $diagram): array
    {
        if (str_starts_with(ltrim($diagram), 'sequenceDiagram')) {
            preg_match_all('/^\s*(?:participant|actor)\s+(\w+)(?:\s+as\s+(.+?))?\s*$/m', $diagram, $matches, PREG_SET_ORDER);

            return array_map(fn (array $m): string => $m[2] ?? $m[1], $matches);
        }

        preg_match_all('/\b\w+(?:\[\[|\[\(|\(\[|\[|\(\(|\(|\{)"?([^"\]\)\}\n]*)"?(?:\]\]|\)\]|\]\)|\]|\)\)|\)|\})/', $diagram, $matches);

        return $matches[1];
    }
}
