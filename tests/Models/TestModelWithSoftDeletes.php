<?php

namespace Mauricius\SynchronizedFields\Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class TestModelWithSoftDeletes extends TestModel
{
    use SoftDeletes;
}
