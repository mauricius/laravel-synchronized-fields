<?php

namespace Mauricius\SynchronizedFields\Contracts;

use Illuminate\Database\Eloquent\Model;
use Mauricius\SynchronizedFields\Exceptions\SynchronizedFieldsException;

interface StorageContract
{
    /**
     * Retrieve the fields of the Model.
     *
     * @param  Model $model
     * @param  array $fields
     * @return array
     * @throws SynchronizedFieldsException
     */
    public function retrieve(Model $model, array $fields): array;

    /**
     * Persist the fields of the Model.
     *
     * @param  Model $model
     * @param  array $fields
     * @return array
     * @throws SynchronizedFieldsException
     */
    public function persist(Model $model, array $fields): array;

    /**
     * Delete all fields of the Model.
     *
     * @param  Model $model
     * @param  array $fields
     * @throws SynchronizedFieldsException
     */
    public function delete(Model $model, array $fields): void;
}
