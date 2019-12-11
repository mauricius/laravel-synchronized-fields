<?php

namespace Mauricius\SynchronizedFields\Storage;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Mauricius\SynchronizedFields\Contracts\StorageContract;
use Mauricius\SynchronizedFields\Exceptions\SynchronizedFieldsException;

class DynamoDbDriver implements StorageContract
{
    /**
     * @var \Aws\DynamoDb\DynamoDbClient
     */
    protected $dynamoDbClient;

    /**
     * @var \Aws\DynamoDb\Marshaler
     */
    protected $marshaler;

    /**
     * DynamoModelObserver constructor.
     *
     * @param DynamoDbClient $dynamoDbClient
     * @param Marshaler      $marshaler
     */
    public function __construct(DynamoDbClient $dynamoDbClient, Marshaler $marshaler)
    {
        $this->dynamoDbClient = $dynamoDbClient;
        $this->marshaler = $marshaler;
    }

    /**
     * {@inheritDoc}
     */
    public function retrieve(Model $model, array $fields): array
    {
        $key = [$model->getKeyName() => $model->getKey()];

        try {
            $value = $this->dynamoDbClient->getItem(
                [
                'AttributesToGet' => $fields,
                'TableName' => $model->getTable(),
                'Key' => $this->marshaler->marshalItem($key)
                ]
            );

            return $this->marshaler->unmarshalItem(Arr::only(Arr::get($value, 'Item', []), $fields));
        } catch (\Exception $e) {
            throw new SynchronizedFieldsException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function persist(Model $model, array $fields): array
    {
        $attrs = array_merge(
            Arr::only($model->getAttributes(), $fields),
            [$model->getKeyName() => $model->getKey()]
        );

        try {
            $this->dynamoDbClient->putItem(
                [
                'TableName' => $model->getTable(),
                'Item' => $this->marshaler->marshalItem($attrs),
                ]
            );

            return $fields;
        } catch (\Exception $e) {
            throw new SynchronizedFieldsException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Model $model, array $fields): void
    {
        $key = [$model->getKeyName() => $model->getKey()];

        try {
            $this->dynamoDbClient->deleteItem(
                [
                'TableName' => $model->getTable(),
                'Key' => $this->marshaler->marshalItem($key),
                ]
            );
        } catch (\Exception $e) {
            throw new SynchronizedFieldsException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }
}
