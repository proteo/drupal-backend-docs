<?php

namespace Proteo\DrupalBackendDocs\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Proteo\DrupalBackendDocs\DrupalBackendDocsServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [DrupalBackendDocsServiceProvider::class];
    }
}
