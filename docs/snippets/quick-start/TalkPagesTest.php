<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Proteo\DrupalFront\Testing\FakesDrupal;
use Tests\TestCase;

class TalkPagesTest extends TestCase
{
    use FakesDrupal;

    public function test_the_talk_listing_renders(): void
    {
        Http::fake(['*' => Http::response($this->fakeCollection('talk', 2))]);

        $this->get('/talks')->assertOk();
    }

    public function test_a_talk_page_shows_the_title(): void
    {
        $talk = $this->fakeEntity('talk', ['title' => 'Dune']);

        // Two requests per page: the alias lookup, then the talk itself.
        Http::fake(fn (Request $request) => str_contains($request->url(), 'translate-path')
            ? Http::response($this->fakeAlias('talk', $talk[0]['id']))
            : Http::response($this->fakeItem($talk)));

        $this->get('/talk/'.basename($talk[0]['attributes']['path']))
            ->assertOk()
            ->assertSee('Dune');
    }
}
