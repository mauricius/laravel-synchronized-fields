<?php

namespace Mauricius\SynchronizedFields\Tests\Storage;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Aws\Result;
use Mauricius\SynchronizedFields\Exceptions\SynchronizedFieldsException;
use Mauricius\SynchronizedFields\Storage\DynamoDbDriver;
use Mauricius\SynchronizedFields\Tests\Models\TestModel;
use Mauricius\SynchronizedFields\Tests\TestCase;

class DynamoDbDriverTest extends TestCase
{
    protected $client;

    protected $marshaler;

    protected $driver;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = $this->getMockBuilder(DynamoDbClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItem', 'putItem', 'deleteItem'])
            ->getMock();

        $this->app->instance(DynamoDbClient::class, $this->client);

        $this->marshaler = new Marshaler();

        $this->driver = new DynamoDbDriver($this->client, $this->marshaler);
    }

    /** @test */
    public function it_should_retrieve_the_fields_of_the_model_from_dynamo_db()
    {
        $model = new TestModel(['id' => 1]);

        $this->client->expects($this->once())
            ->method('getItem')
            ->with([
                'AttributesToGet' => ['test_field'],
                'TableName' => 'test_models',
                'Key' => $this->marshaler->marshalItem(['id' => 1])
            ])
            ->willReturn($this->mockAwsResult([
                'test_field' => [
                    'key' => 'value'
                ]
            ]));

        $result = $this->driver->retrieve($model, ['test_field']);

        $this->assertEquals([
            'test_field' => [
                'key' => 'value'
            ]
        ], $result);
    }

    /** @test */
    public function it_should_throw_an_exception_if_the_dynamo_db_resource_is_not_found()
    {
        $this->expectException(SynchronizedFieldsException::class);

        $model = new TestModel(['id' => 1]);

        $this->client->expects($this->once())
            ->method('getItem')
            ->with([
                'AttributesToGet' => ['test_field'],
                'TableName' => 'test_models',
                'Key' => $this->marshaler->marshalItem(['id' => 1])
            ])
            ->willThrowException(new SynchronizedFieldsException());

        $this->driver->retrieve($model, ['test_field']);
    }

    /** @test */
    public function it_should_persist_model_fields_to_dynamo_db()
    {
        $model = new TestModel([
            'id' => 1,
            'test_field' => [
                'key' => 'value'
            ]
        ]);

        $this->client->expects($this->once())
            ->method('putItem')
            ->with([
                'TableName' => 'test_models',
                'Item' => $this->marshaler->marshalItem([
                    'id' => 1,
                    'test_field' => [
                        'key' => 'value'
                    ]
                ]),
            ]);

        $this->driver->persist($model, ['test_field']);
    }

    /** @test */
    public function it_should_not_persist_fields_of_the_model_if_the_dynamo_db_resource_is_not_found()
    {
        $this->expectException(SynchronizedFieldsException::class);

        $model = new TestModel(['id' => 1]);

        $this->client->expects($this->once())
            ->method('putItem')
            ->willThrowException(new SynchronizedFieldsException());

        $this->driver->persist($model, ['test_field']);
    }

    /** @test */
    public function it_should_delete_the_dynamo_db_entry_when_deleting_the_model()
    {
        $model = new TestModel([
            'id' => 1,
            'test_field' => [
                'key' => 'value'
            ]
        ]);

        $this->client->expects($this->once())
            ->method('deleteItem')
            ->with([
                'TableName' => 'test_models',
                'Key' => $this->marshaler->marshalItem(['id' => 1])
            ]);

        $this->driver->delete($model, ['test_field']);
    }

    protected function mockAwsResult(array $result)
    {
        return new Result([
            'Item' => $this->marshaler->marshalItem($result)
        ]);
    }
}
