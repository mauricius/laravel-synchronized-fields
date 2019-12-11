<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mauricius\SynchronizedFields\Tests\Models\TestModel;

class CreateTable extends Migration
{
    public function up()
    {
        Schema::create($this->getTable(), function (Blueprint $table) {
            $table->increments('id');
            $table->string('field');
            $table->text('sync_field')->nullable();
            $table->text('another_sync_field')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function getTable()
    {
        return with(new TestModel())->getTable();
    }
}
