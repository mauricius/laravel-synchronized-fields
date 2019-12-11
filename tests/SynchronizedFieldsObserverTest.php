<?php

namespace Mauricius\SynchronizedFields\Tests;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mauricius\SynchronizedFields\Contracts\StorageContract;
use Mauricius\SynchronizedFields\Tests\Models\TestModel;
use Mauricius\SynchronizedFields\Tests\Models\TestModelWithAccessorAndMutator;
use Mauricius\SynchronizedFields\Tests\Models\TestModelWithSoftDeletes;

class SynchronizedFieldsObserverTest extends TestCase
{
    protected $storage;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();

        $this->storage = $this->getMockBuilder(StorageContract::class)
            ->onlyMethods(['retrieve', 'persist', 'delete'])
            ->getMock();

        $this->app->instance(StorageContract::class, $this->storage);
    }

    protected function setUpDatabase()
    {
        Schema::dropAllTables();

        include_once __DIR__.'/migrations/create_table.php';

        (new \CreateTable())->up();
    }

    /** @test */
    public function it_should_get_the_synchronized_fields_property_on_the_model()
    {
        $model = new TestModel();

        $this->assertEquals(['sync_field', 'another_sync_field'], $model->getSynchronizedFields());
    }

    /** @test */
    public function it_should_return_an_empty_array_if_the_model_does_not_define_its_synchronized_fields_property()
    {
        TestModel::setSynchronizedFields([]);

        $this->assertEmpty(TestModel::getSynchronizedFields());
    }

    /** @test */
    public function it_should_return_the_original_fields_if_are_already_defined()
    {
        $id = DB::table($this->getTable())->insertGetId([
            'field' => 'value',
            'sync_field' => json_encode(['key' => 'value']),
            'another_sync_field' => json_encode(['foo' => 'bar']),
        ]);

        $this->storage->expects($this->never())
            ->method('retrieve');

        $model = TestModel::find($id);

        $this->assertEquals(['key' => 'value'], $model->sync_field);
        $this->assertEquals(['foo' => 'bar'], $model->another_sync_field);
    }

    /** @test */
    public function it_should_return_the_synchronized_field_from_the_storage_if_the_original_value_is_not_set_on_the_model()
    {
        $id = DB::table($this->getTable())->insertGetId([
            'field' => 'value',
            'sync_field' => json_encode(['key' => 'value']),
            'another_sync_field' => null,
        ]);

        $this->storage->expects($this->once())
            ->method('retrieve')
            ->with($this->isInstanceOf(TestModel::class), ['another_sync_field'])
            ->willReturn([
                'another_sync_field' => json_encode(['foo' => 'bar'])
            ]);

        $model = TestModel::find($id);

        $this->assertEquals(['key' => 'value'], $model->sync_field);
        $this->assertEquals(['foo' => 'bar'], $model->another_sync_field);
    }

    /** @test */
    public function it_should_persist_the_synchronized_fields_after_saving_the_model()
    {
        $model = new TestModel();
        $model->field = 'test';
        $model->sync_field = json_encode(['key' => 'value']);
        $model->another_sync_field = json_encode(['foo' => 'bar']);

        $this->storage->expects($this->once())
            ->method('persist')
            ->with($model, ['sync_field', 'another_sync_field'])
            ->willReturn(['sync_field', 'another_sync_field']);

        $model->save();
    }

    /** @test */
    public function it_should_not_synchronize_fields_if_the_model_is_not_updated_at_all()
    {
        $id = DB::table($this->getTable())->insertGetId([
            'field' => 'value',
            'sync_field' => json_encode(['key' => 'value']),
            'another_sync_field' => json_encode(['foo' => 'bar']),
        ]);

        $model = TestModel::find($id);

        $this->storage->expects($this->never())
            ->method('persist');

        $model->save();
    }

    /** @test */
    public function it_should_only_synchronize_updated_fields_when_the_model_is_updated()
    {
        $id = DB::table($this->getTable())->insertGetId([
            'field' => 'value',
            'sync_field' => json_encode(['key' => 'value']),
            'another_sync_field' => null,
        ]);

        $model = TestModel::find($id);
        $model->another_sync_field = ['foo' => 'bar'];

        $this->storage->expects($this->once())
            ->method('persist')
            ->with($model, ['another_sync_field'])
            ->willReturn(['another_sync_field']);

        $model->save();
    }

    /** @test */
    public function it_should_set_all_the_original_synchronized_fields_to_null_if_replicate_config_value_is_set_to_false()
    {
        Config::set('synchronized-fields.replicate', false);

        $model = new TestModel();
        $model->field = 'test';
        $model->sync_field = json_encode(['key' => 'value']);
        $model->another_sync_field = json_encode(['foo' => 'bar']);

        $this->storage->expects($this->once())
            ->method('persist')
            ->with($model, ['sync_field', 'another_sync_field'])
            ->willReturn(['sync_field', 'another_sync_field']);

        $model->save();

        $this->assertDatabaseHas($this->getTable(), [
            'id' => $model->id,
            'field' => 'test',
            'sync_field' => null,
            'another_sync_field' => null
        ]);
    }

    /** @test */
    public function it_should_delete_all_the_synchronized_fields_from_the_storage_when_the_model_is_deleted()
    {
        $id = DB::table($this->getTable())->insertGetId([
            'field' => 'value',
            'sync_field' => json_encode(['key' => 'value']),
            'another_sync_field' => json_encode(['foo' => 'bar'])
        ]);

        $model = TestModel::find($id);

        $this->storage->expects($this->once())
            ->method('delete')
            ->with($model, ['sync_field', 'another_sync_field']);

        $model->delete();
    }

    /** @test */
    public function it_should_not_delete_all_the_synchronized_fields_from_the_storage_when_the_model_is_soft_deleted()
    {
        $id = DB::table($this->getTable())->insertGetId([
            'field' => 'value',
            'sync_field' => json_encode(['key' => 'value']),
            'another_sync_field' => json_encode(['foo' => 'bar'])
        ]);

        $model = TestModelWithSoftDeletes::find($id);

        $this->storage->expects($this->never())
            ->method('delete');

        $model->delete();
    }

    /** @test */
    public function it_should_delete_all_the_synchronized_fields_from_the_storage_when_the_model_is_force_deleted()
    {
        $id = DB::table($this->getTable())->insertGetId([
            'field' => 'value',
            'sync_field' => json_encode(['key' => 'value']),
            'another_sync_field' => json_encode(['foo' => 'bar'])
        ]);

        $model = TestModelWithSoftDeletes::find($id);

        $this->storage->expects($this->once())
            ->method('delete')
            ->with($model, ['sync_field', 'another_sync_field']);

        $model->forceDelete();
    }

    /** @test */
    public function it_should_respect_model_accessors_when_retrieving_the_synchronized_fields_from_the_model()
    {
        $id = DB::table($this->getTable())->insertGetId([
            'field' => 'value',
            'sync_field' => null,
            'another_sync_field' => json_encode(['foo' => 'bar']),
        ]);

        $this->storage->expects($this->once())
            ->method('retrieve')
            ->with($this->isInstanceOf(TestModelWithAccessorAndMutator::class), ['sync_field'])
            ->willReturn([
                'sync_field' => json_encode(['key' => 'value'])
            ]);

        $model = TestModelWithAccessorAndMutator::find($id);

        $this->assertEquals(['key' => 'value_mutated'], $model->sync_field);
    }

    /** @test */
    public function it_should_respect_model_mutators_when_storing_the_synchronized_fields_of_the_model()
    {
        $model = new TestModelWithAccessorAndMutator();
        $model->field = 'test';
        // sync_field has a mutator, which has precedence
        // over casting, therefore we need to json_encode
        $model->sync_field = json_encode(['key' => 'value']);
        $model->another_sync_field = ['foo' => 'bar'];

        $this->storage->expects($this->once())
            ->method('persist')
            ->with($model, ['sync_field', 'another_sync_field'])
            ->willReturn(['sync_field', 'another_sync_field']);

        $model->save();

        $this->assertEquals(['another_key' => 'another_value_mutated'], $model->refresh()->sync_field);
        $this->assertEquals(['foo' => 'bar'], $model->refresh()->another_sync_field);
    }

    /** @test */
    public function it_should_explicitly_ignore_specific_synchronized_fields_when_working_with_the_model()
    {
        $id = DB::table($this->getTable())->insertGetId([
            'field' => 'value',
            'sync_field' => null,
            'another_sync_field' => json_encode(['foo' => 'bar'])
        ]);

        $this->storage->expects($this->never())
            ->method('retrieve');

        $model = TestModel::ignoringSynchronizedFields(['sync_field'], function () use ($id) {
            return TestModel::find($id);
        });

        $this->assertNull($model->sync_field);
        $this->assertEquals(['foo' => 'bar'], $model->another_sync_field);
    }

    /** @test */
    public function it_should_explicitly_ignore_all_synchronized_fields_when_working_with_the_model()
    {
        $id = DB::table($this->getTable())->insertGetId([
            'field' => 'value',
            'sync_field' => null,
            'another_sync_field' => null
        ]);

        $this->storage->expects($this->never())
            ->method('retrieve');

        $model = TestModel::withoutSynchronizedFields(function () use ($id) {
            return TestModel::find($id);
        });

        $this->assertNull($model->sync_field);
        $this->assertNull($model->another_sync_field);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        TestModel::setSynchronizedFields(['sync_field', 'another_sync_field']);
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
}
