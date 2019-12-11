<?php

namespace Mauricius\SynchronizedFields\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Mauricius\SynchronizedFields\Traits\SynchronizedFields;

class TestModel extends Model
{
    use SynchronizedFields;

    /**
     * {@inheritDoc}
     */
    public $table = 'test_models';

    /**
     * {@inheritDoc}
     */
    protected $guarded = [];

    /**
     * {@inheritDoc}
     */
    protected $casts = [
        'sync_field' => 'array',
        'another_sync_field' => 'array'
    ];

    /**
     * The attributes that should be synchronized.
     *
     * @var array
     */
    protected static $synchronizedFields = [
        'sync_field',
        'another_sync_field'
    ];
}
