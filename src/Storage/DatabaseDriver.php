<?php

namespace Mauricius\SynchronizedFields\Storage;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Mauricius\SynchronizedFields\Contracts\StorageContract;
use Mauricius\SynchronizedFields\Exceptions\SynchronizedFieldsException;

class DatabaseDriver implements StorageContract
{
    /**
     * @var Connection
     */
    protected $database;

    /**
     * DatabaseDriver constructor.
     *
     * @param Connection $database
     */
    public function __construct(Connection $database)
    {
        $this->database = $database;
    }

    /**
     * {@inheritDoc}
     */
    public function retrieve(Model $model, array $fields): array
    {
        try {
            $result = $this->database
                ->table($model->getTable())
                ->select($fields)
                ->find($model->getKey());

            return (array) $result;
        } catch (\Exception $e) {
            // see https://www.php.net/manual/en/class.pdoexception.php#95812
            throw new SynchronizedFieldsException($e->getMessage(), (int) $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function persist(Model $model, array $fields): array
    {
        try {
            $this->database
                ->table($model->getTable())
                ->updateOrInsert(
                    [$model->getKeyName() => $model->getKey()],
                    Arr::only($model->getAttributes(), $fields)
                );

            return $fields;
        } catch (\Exception $e) {
            throw new SynchronizedFieldsException($e->getMessage(), (int) $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Model $model, array $fields): void
    {
        try {
            $this->database
                ->table($model->getTable())
                ->where($model->getKeyName(), '=', $model->getKey())
                ->delete();
        } catch (\Exception $e) {
            throw new SynchronizedFieldsException($e->getMessage(), (int) $e->getCode(), $e->getPrevious());
        }
    }
}
