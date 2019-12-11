<?php

namespace Mauricius\SynchronizedFields;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Mauricius\SynchronizedFields\Contracts\ObserverContract;
use Mauricius\SynchronizedFields\Contracts\StorageContract;

class SynchronizedFieldsObserver implements ObserverContract
{
    /**
     * @var StorageContract
     */
    protected $storage;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * SynchronizedFieldsObserver constructor.
     *
     * @param StorageContract $storage
     * @param Connection      $connection
     */
    public function __construct(StorageContract $storage, Connection $connection)
    {
        $this->storage = $storage;
        $this->connection = $connection;
    }

    /**
     * {@inheritDoc}
     */
    public function retrieved(Model $model)
    {
        if (! $model->getSynchronizedFields()) {
            return;
        }

        $fields = array_values(
            array_filter($model->getSynchronizedFields(), function ($field) use ($model) {
                if ($model->getOriginal($field)) {
                    return false;
                }

                return true;
            })
        );

        if (! $fields) {
            return;
        }

        $fields = $this->storage->retrieve($model, $fields);

        foreach ($fields as $key => $value) {
            $model->setRawAttribute($key, $value);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function saved(Model $model)
    {
        if ($model->isClean()) {
            return;
        }

        $fields = array_keys(
            Arr::only($model->getDirty(), $model->getSynchronizedFields())
        );

        if (! $fields) {
            return;
        }

        $updated = $this->storage->persist($model, $fields);

        if (Config::get('synchronized-fields.replicate') === false) {
            $this->connection->table($model->getTable())
                ->where('id', '=', $model->getKey())
                ->update(invert_and_nullify($updated));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function deleted(Model $model)
    {
        if (! $this->shouldDelete($model)) {
            return;
        }

        $this->storage->delete($model, $model->getSynchronizedFields());
    }

    /**
     * Determine if the delete call was a soft delete.
     *
     * @param  Model $model
     * @return bool
     */
    protected function shouldDelete(Model $model)
    {
        if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
            if (! $model->isForceDeleting()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Force the synchronization of fields.
     *
     * @param  Model $model
     * @return array
     * @throws Exceptions\SynchronizedFieldsException
     */
    public function forceSynchonization(Model $model)
    {
        $updated = $this->storage->persist($model, $model->getSynchronizedFields());

        if (Config::get('synchronized-fields.replicate') === false) {
            $this->connection->table($model->getTable())
                ->where('id', '=', $model->getKey())
                ->update(invert_and_nullify($updated));
        }

        return $updated;
    }
}
