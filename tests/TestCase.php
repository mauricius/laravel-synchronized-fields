<?php

namespace Mauricius\SynchronizedFields\Tests;

use Mauricius\SynchronizedFields\SynchronizedFieldsServiceProvider;
use Mauricius\SynchronizedFields\Tests\Models\TestModel;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [SynchronizedFieldsServiceProvider::class];
    }

    /**
     * Get the name of the test table.
     *
     * @return string
     */
    protected function getTable(): string
    {
        return with(new TestModel())->getTable();
    }

}
