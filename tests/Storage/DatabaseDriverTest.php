<?php

namespace Mauricius\SynchronizedFields\Tests\Handler\Storage;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mauricius\SynchronizedFields\Exceptions\SynchronizedFieldsException;
use Mauricius\SynchronizedFields\Storage\DatabaseDriver;
use Mauricius\SynchronizedFields\Tests\Models\TestModel;
use Mauricius\SynchronizedFields\Tests\TestCase;

class DatabaseDriverTest extends TestCase
{
    protected $connection;
    protected $driver;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();

        $this->connection = $this->app['db.connection'];

        $this->driver = new DatabaseDriver($this->connection);
    }

    protected function setUpDatabase()
    {
        Schema::dropAllTables();

        Schema::create($this->getTable(), function (Blueprint $table) {
            $table->increments('id');
            $table->text('sync_field');
            $table->text('another_sync_field');
        });
    }

    /** @test */
    public function it_should_retrieve_the_fields_of_the_model_from_the_database()
    {
        $this->connection->table($this->getTable())->insert([
            'sync_field' => json_encode(['key' => 'value']),
            'another_sync_field' => json_encode(['foo' => 'bar'])
        ]);

        $model = new TestModel(['id' => 1]);

        $result = $this->driver->retrieve($model, ['sync_field', 'another_sync_field']);

        $this->assertEquals([
            'sync_field' => json_encode(['key' => 'value']),
            'another_sync_field' => json_encode(['foo' => 'bar'])
        ], $result);
    }

    /** @test */
    public function it_should_not_return_any_field_if_they_are_not_found_in_the_database()
    {
        $model = new TestModel(['id' => 1]);

        $result = $this->driver->retrieve($model, ['sync_field', 'another_sync_field']);

        $this->assertEmpty($result);
    }

    /** @test */
    public function it_should_throw_an_exception_if_the_synchronized_field_table_is_not_found()
    {
        $this->expectException(SynchronizedFieldsException::class);

        Schema::drop($this->getTable());

        $model = new TestModel(['id' => 1]);

        $this->driver->retrieve($model, ['sync_field', 'another_sync_field']);
    }

    /** @test */
    public function it_should_persist_model_fields_to_the_database()
    {
        $model = new TestModel([
            'id' => 1,
            'sync_field' => [
                'key' => 'value'
            ],
            'another_sync_field' => [
                'foo' => 'bar'
            ]
        ]);

        $fields = $this->driver->persist($model, ['sync_field', 'another_sync_field']);

        $this->assertEquals(['sync_field', 'another_sync_field'], $fields);
        $this->assertDatabaseHas($this->getTable(), [
            'sync_field' => json_encode(['key' => 'value']),
            'another_sync_field' => json_encode(['foo' => 'bar'])
        ]);
    }

    /** @test */
    public function it_should_not_persist_fields_of_the_model_if_the_database_table_is_not_found()
    {
        $this->expectException(SynchronizedFieldsException::class);

        Schema::drop($this->getTable());

        $model = new TestModel([
            'id' => 1,
            'sync_field' => [
                'key' => 'value'
            ],
            'another_sync_field' => [
                'foo' => 'bar'
            ]
        ]);

        $this->driver->persist($model, ['sync_field', 'another_sync_field']);
    }

    /** @test */
    public function it_should_delete_the_synchronized_fields_database_row_when_deleting_the_model()
    {
        $this->connection->table($this->getTable())->insert([
            'sync_field' => json_encode(['key' => 'value']),
            'another_sync_field' => json_encode(['foo' => 'bar'])
        ]);

        $model = new TestModel(['id' => 1]);

        $this->driver->delete($model, ['sync_field', 'another_sync_field']);

        $this->assertDatabaseMissing($this->getTable(), [
            'sync_field',
            'another_sync_field'
        ]);
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
