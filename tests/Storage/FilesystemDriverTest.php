<?php

namespace Mauricius\SynchronizedFields\Tests\Storage;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Mauricius\SynchronizedFields\Storage\FilesystemDriver;
use Mauricius\SynchronizedFields\Tests\Models\TestModel;
use Mauricius\SynchronizedFields\Tests\TestCase;

class FilesystemDriverTest extends TestCase
{
    protected $filesystem;

    public function setUp(): void
    {
        parent::setUp();

        $this->filesystem = Storage::fake('test');

        Config::set('synchronized-fields.filesystem.disk', 'test');
    }

    /** @test */
    public function it_should_get_the_full_path_where_the_model_field_will_be_saved()
    {
        $model = new TestModel(['id' => 1]);

        $driver = new FilesystemDriver($this->filesystem);

        $this->assertEquals('test_models/0/1@test_field.json', $driver->getFullPath($model, 'test_field'));
    }

    /** @test */
    public function it_should_get_the_full_path_where_the_model_field_will_be_saved_when_the_id_is_higher_than_files_per_folder_config_value()
    {
        Config::set('synchronized-fields.filesystem.files_per_folder', 500);

        $model = new TestModel(['id' => 501]);

        $driver = new FilesystemDriver($this->filesystem);

        $this->assertEquals('test_models/1/501@test_field.json', $driver->getFullPath($model, 'test_field'));
    }

    /** @test */
    public function it_should_retrieve_a_single_field_of_the_model()
    {
        $target = 'test_models/0/';

        $this->makeDirectory($target);

        $this->filesystem->put($target . '1@test_field.json', json_encode([
            'key' => 'value'
        ]));

        $model = new TestModel(['id' => 1]);

        $driver = new FilesystemDriver($this->filesystem);

        $fields = $driver->retrieve($model, ['test_field']);

        $this->assertEquals([
            'test_field' => json_encode(['key' => 'value'])
        ], $fields);
    }

    /** @test */
    public function it_should_retrieve_multiple_fields_of_the_model()
    {
        $target = 'test_models/0/';

        $this->makeDirectory($target);

        $this->filesystem->put($target . '1@test_field.json', json_encode([
            'key' => 'value'
        ]));
        $this->filesystem->put($target . '1@another_test_field.json', json_encode([
            'foo' => 'bar'
        ]));

        $model = new TestModel(['id' => 1]);

        $driver = new FilesystemDriver($this->filesystem);

        $fields = $driver->retrieve($model, ['test_field', 'another_test_field']);

        $this->assertEquals([
            'test_field' => json_encode(['key' => 'value']),
            'another_test_field' => json_encode(['foo' => 'bar'])
        ], $fields);
    }

    /** @test */
    public function it_should_not_return_a_field_if_it_does_not_exist()
    {
        $target = 'test_models/0/';

        $this->makeDirectory($target);

        $this->filesystem->put($target . '1@test_field.json', json_encode([
            'key' => 'value'
        ]));

        $model = new TestModel(['id' => 1]);

        $driver = new FilesystemDriver($this->filesystem);

        $fields = $driver->retrieve($model, ['test_field', 'missing_field']);

        $this->assertEquals([
            'test_field' => json_encode(['key' => 'value']),
        ], $fields);
    }

    /** @test */
    public function it_should_persist_a_field_of_the_model()
    {
        $model = new TestModel([
            'id' => 1,
            'test_field' => [
                'key' => 'value'
            ]
        ]);

        $driver = new FilesystemDriver($this->filesystem);

        $fields = $driver->persist($model, ['test_field']);

        $this->assertEquals(['test_field'], $fields);
    }

    /** @test */
    public function it_should_persist_multiple_fields_of_the_model()
    {
        $model = new TestModel([
            'id' => 1,
            'test_field' => [
                'key' => 'value'
            ],
            'another_test_field' => [
                'foo' => 'bar'
            ]
        ]);

        $driver = new FilesystemDriver($this->filesystem);

        $fields = $driver->persist($model, ['test_field', 'another_test_field']);

        $this->assertEquals(['test_field', 'another_test_field'], $fields);
    }

    /** @test */
    public function it_should_delete_the_json_file_when_setting_the_corresponding_field_to_null()
    {
        $target = 'test_models/0/';

        $this->makeDirectory($target);

        $this->filesystem->put($target . '1@test_field.json', json_encode([
            'key' => 'value'
        ]));

        $model = new TestModel([
            'id' => 1,
            'test_field' => null,
            'another_test_field' => [
                'foo' => 'bar'
            ]
        ]);

        $driver = new FilesystemDriver($this->filesystem);

        $fields = $driver->persist($model, ['test_field', 'another_test_field']);

        $this->assertEquals(['another_test_field'], $fields);
        $this->assertFalse($this->filesystem->exists($target . '1@test_field.json'));
        $this->assertTrue($this->filesystem->exists($target . '1@another_test_field.json'));
    }

    /** @test */
    public function it_should_delete_all_json_files_when_deleting_the_model()
    {
        $target = 'test_models/0/';

        $this->makeDirectory($target);

        $this->filesystem->put($target . '1@test_field.json', json_encode([
            'key' => 'value'
        ]));
        $this->filesystem->put($target . '1@another_test_field.json', json_encode([
            'foo' => 'bar'
        ]));

        $model = new TestModel(['id' => 1]);

        $driver = new FilesystemDriver($this->filesystem);

        $driver->delete($model, ['test_field', 'another_test_field']);

        $this->assertFalse($this->filesystem->exists($target . '1@test_field.json'));
        $this->assertFalse($this->filesystem->exists($target . '1@another_test_field.json'));
    }

    protected function makeDirectory($dir)
    {
        $this->filesystem->makeDirectory($dir, 0777, true);
    }
}
