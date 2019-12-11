<?php

namespace Mauricius\SynchronizedFields\Contracts;

use Illuminate\Database\Eloquent\Model;

interface ObserverContract
{
    /**
     * @param  Model $model
     * @return mixed
     */
    public function retrieved(Model $model);

    /**
     * @param  Model $model
     * @return mixed
     */
    public function saved(Model $model);

    /**
     * @param  Model $model
     * @return mixed
     */
    public function deleted(Model $model);
}
