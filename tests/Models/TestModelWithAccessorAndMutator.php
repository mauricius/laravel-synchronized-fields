<?php

namespace Mauricius\SynchronizedFields\Tests\Models;

class TestModelWithAccessorAndMutator extends TestModel
{
    /**
     * NOT: if mutator exists on the model,
     * casting never happens, therefore
     * $value is a string.
     *
     * @param  string $value
     * @return string
     */
    public function getSyncFieldAttribute($value)
    {
        return array_map(function ($val) {
            return $val . '_mutated';
        }, json_decode($value, true));
    }

    /**
     * @param  string $value
     * @return void
     */
    public function setSyncFieldAttribute($value)
    {
        $this->attributes['sync_field'] = json_encode(['another_key' => 'another_value']);
    }
}
