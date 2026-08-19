<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function defineEnvironment($app): void
    {
        $compiled = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ainchors-test-views';
        if (! is_dir($compiled)) {
            mkdir($compiled, 0777, true);
        }

        $app['config']->set('view.compiled', $compiled);
    }
}
