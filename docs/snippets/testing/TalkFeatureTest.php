<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Proteo\DrupalFront\Testing\FakesDrupal;
use Tests\TestCase;

class TalkFeatureTest extends TestCase
{
    use FakesDrupal;

    public function test_a_talk_page_shows_the_title_you_set(): void
    {
        $talk = $this->fakeEntity('talk', ['title' => 'Dune']);

        $this->fakeTalk($talk);

        $this->get($this->urlOf($talk))
            ->assertOk()
            ->assertSee('Dune');
    }

    public function test_an_unknown_talk_is_a_404(): void
    {
        Http::fake(['*' => Http::response([], 404)]);

        $this->get('/talk/unknown')->assertNotFound();
    }

    public function test_an_unreachable_backend_is_a_503_with_retry_after(): void
    {
        Http::fake(['*' => Http::response('', 503)]);

        $this->get('/talk/any')->assertStatus(503)->assertHeader('Retry-After', '30');
    }

    public function test_a_talk_without_its_required_title_is_a_502(): void
    {
        // An explicit null leaves the attribute empty instead of getting a sample value.
        $talk = $this->fakeEntity('talk', ['title' => null]);

        $this->fakeTalk($talk);

        $this->get($this->urlOf($talk))->assertStatus(502);
    }

    /**
     * Answers the two requests behind a page: the alias lookup, then the item.
     *
     * @param  array{0: array<string, mixed>, 1: list<array<string, mixed>>}  $talk
     */
    private function fakeTalk(array $talk): void
    {
        Http::fake(fn (Request $request) => str_contains($request->url(), 'translate-path')
            ? Http::response($this->fakeAlias('talk', $talk[0]['id']))
            : Http::response($this->fakeItem($talk)));
    }

    /**
     * @param  array{0: array<string, mixed>, 1: list<array<string, mixed>>}  $talk
     */
    private function urlOf(array $talk): string
    {
        return '/talk/'.basename($talk[0]['attributes']['path']);
    }
}
