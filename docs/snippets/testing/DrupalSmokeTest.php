<?php

namespace Tests\Feature;

use Proteo\DrupalFront\Testing\DescriptorSmokeTests;
use Tests\TestCase;

/**
 * Checks your real descriptor: it validates, and every routed page renders.
 */
class DrupalSmokeTest extends TestCase
{
    use DescriptorSmokeTests;
}
