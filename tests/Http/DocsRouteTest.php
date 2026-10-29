<?php

namespace Proteo\DrupalBackendDocs\Tests\Http;

use Proteo\DrupalBackendDocs\Tests\TestCase;

class DocsRouteTest extends TestCase
{
    public function test_the_docs_are_served_outside_production(): void
    {
        $response = $this->get('/backend-docs/');

        $response->assertOk();
        $this->assertStringContainsString('text/html', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
    }

    public function test_the_route_is_named(): void
    {
        $this->assertSame(url('/backend-docs'), route('drupal-backend-docs.docs'));
    }

    public function test_production_answers_404(): void
    {
        $this->app['env'] = 'production';

        $this->get('/backend-docs/')->assertNotFound();
    }

    public function test_docs_enabled_false_answers_404(): void
    {
        $this->app['config']->set('drupal-backend-docs.enabled', false);

        $this->get('/backend-docs/')->assertNotFound();
    }

    public function test_docs_enabled_true_overrides_production(): void
    {
        $this->app['env'] = 'production';
        $this->app['config']->set('drupal-backend-docs.enabled', true);

        $this->get('/backend-docs/')->assertOk();
    }

    public function test_clean_urls_resolve_to_html_and_index_files(): void
    {
        $this->get('/backend-docs/architecture')->assertOk();
        $this->get('/backend-docs/architecture.html')->assertOk();
        $this->get('/backend-docs/adr')->assertOk();
    }

    public function test_deep_assets_get_their_mime_type(): void
    {
        $asset = collect(glob(__DIR__.'/../../public/assets/*.css'))->first();

        $response = $this->get('/backend-docs/assets/'.basename($asset));

        $response->assertOk();
        $this->assertStringContainsString('text/css', $response->headers->get('Content-Type'));
    }

    public function test_traversal_and_unknown_files_answer_404(): void
    {
        $this->get('/backend-docs/%2e%2e/composer.json')->assertNotFound();
        $this->get('/backend-docs/..%2f..%2fcomposer.json')->assertNotFound();
        $this->get('/backend-docs/assets/../../composer.json')->assertNotFound();
        $this->get('/backend-docs/missing')->assertNotFound();
        $this->get('/backend-docs/assets')->assertNotFound();
    }
}
