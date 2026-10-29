<?php

namespace Proteo\DrupalBackendDocs\Tests\Http;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Proteo\DrupalBackendDocs\DocsSite;
use Proteo\DrupalBackendDocs\Tests\TestCase;

class PublicPathTest extends TestCase
{
    protected function tearDown(): void
    {
        File::deleteDirectory(DocsSite::publishedRoot());
        File::deleteDirectory(DocsSite::publishedRoot().'.tmp');

        parent::tearDown();
    }

    protected function defineEnvironment($app): void
    {
        if (str_contains($this->name(), 'custom')) {
            $app['config']->set('drupal-backend-docs.public_path', 'internal/docs');
        }
    }

    public function test_the_default_path_needs_no_publish(): void
    {
        $this->get('/backend-docs/')->assertOk();
    }

    public function test_custom_path_answers_404_until_published(): void
    {
        $this->get('/internal/docs/')->assertNotFound();
        $this->get('/backend-docs/')->assertNotFound();
    }

    public function test_custom_path_is_served_after_publish(): void
    {
        $this->artisan('drupal-backend-docs:publish')->assertSuccessful();

        $response = $this->get('/internal/docs/');
        $response->assertOk();
        $html = $response->getFile()->getContent();
        $this->assertStringContainsString('/internal/docs/assets/', $html);
        $this->assertStringNotContainsString('/backend-docs', $html);
        $this->get('/internal/docs/architecture')->assertOk();
        $this->assertSame(url('/internal/docs'), route('drupal-backend-docs.docs'));
    }

    public function test_custom_path_goes_stale_when_the_manifest_differs(): void
    {
        $this->artisan('drupal-backend-docs:publish')->assertSuccessful();
        file_put_contents(DocsSite::publishedRoot().'/manifest.json', json_encode(['public_path' => 'other', 'build' => DocsSite::buildHash()]));

        $this->get('/internal/docs/')->assertNotFound();
    }

    public function test_normalise_accepts_and_trims_valid_paths(): void
    {
        $this->assertSame('a/b-c_1', DocsSite::normalise('/a/b-c_1/'));
    }

    public function test_normalise_rejects_invalid_paths(): void
    {
        foreach (['', '/', 'Docs', 'a/../b', 'a\\b', 'a b', 'a//b'] as $value) {
            try {
                DocsSite::normalise($value);
                $this->fail("[{$value}] should be rejected");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
