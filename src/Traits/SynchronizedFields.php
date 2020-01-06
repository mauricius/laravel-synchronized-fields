<?php

namespace Mauricius\SynchronizedFields\Traits;

use Illuminate\Support\Facades\App;
use Mauricius\SynchronizedFields\Contracts\ObserverContract;

trait SynchronizedFields
{
    /**
     * Boot the Trait
     */
    public static function bootSynchronizedFields()
    {
        static::observe(App::make(ObserverContract::class));
    }

    /**
     * @param $key
     * @param $value
     */
    public function setRawAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Get which fields have to be synchronized.
     *
     * @return array
     */
    public static function getSynchronizedFields(): array
    {
        return self::$synchronizedFields ?? [];
    }

    /**
     * Set which fields have to be synchronized.
     *
     * @param array $fields
     */
    public static function setSynchronizedFields(array $fields)
    {
        self::$synchronizedFields = $fields;
    }

    /**
     * Run the callback ignoring specific synchronized fields.
     *
     * @param  array    $ignoringFields
     * @param  callable $callback
     * @return mixed
     */
    public static function ignoringSynchronizedFields(array $ignoringFields, callable $callback)
    {
        $fields = static::getSynchronizedFields();

        static::setSynchronizedFields(
            array_values(
                array_diff(static::getSynchronizedFields(), $ignoringFields)
            )
        );

        try {
            return $callback();
        } finally {
            static::setSynchronizedFields($fields);
        }
    }

    /**
     * Run the callback ignoring all synchronized fields.
     *
     * @param  callable $callback
     * @return mixed
     */
    public static function withoutSynchronizedFields(callable $callback)
    {
        $fields = static::getSynchronizedFields();

        static::setSynchronizedFields([]);

        try {
            return $callback();
        } finally {
            static::setSynchronizedFields($fields);
        }
    }

    /**
     * Force synchronization of fields.
     */
    public function forceSynchonization()
    {
        $observer = App::make(ObserverContract::class);

        $observer->forceSynchonization($this);
    }
}
