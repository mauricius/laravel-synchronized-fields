<?php

namespace Mauricius\SynchronizedFields;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Mauricius\SynchronizedFields\Contracts\ObserverContract;
use Mauricius\SynchronizedFields\Contracts\StorageContract;
use Mauricius\SynchronizedFields\Observers\DynamoObserver;
use Mauricius\SynchronizedFields\Observers\FilesystemObserver;
use Mauricius\SynchronizedFields\Storage\DatabaseDriver;
use Mauricius\SynchronizedFields\Storage\DynamoDbDriver;
use Mauricius\SynchronizedFields\Storage\FilesystemDriver;

class SynchronizedFieldsServiceProvider extends ServiceProvider
{
    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function boot()
    {
        if (! $this->app['config']->get('synchronized-fields.enabled')) {
            return;
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../config/synchronized-fields.php' => config_path('synchronized-fields.php')], 'config');
        }
    }

    /**
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/synchronized-fields.php', 'synchronized-fields');

        if (! $this->app['config']->get('synchronized-fields.enabled')) {
            return;
        }

        if ($this->app['config']->get('synchronized-fields.driver') === 'dynamo') {
            $this->registerDynamoClient();
        }

        $this->app->singleton(StorageContract::class, function () {
            $driver = $this->app['config']->get('synchronized-fields.driver');

            switch ($driver) {
                case 'dynamo':
                    return new DynamoDbDriver(
                        App::make(DynamoDbClient::class),
                        App::make(Marshaler::class)
                    );

                    break;

                case 'database':
                    return new DatabaseDriver(
                        $this->app['db']->connection(
                            $this->app['config']->get('synchronized-fields.database.connection')
                        )
                    );

                    break;

                case 'filesystem':
                default:
                    return new FilesystemDriver(
                        $this->app['filesystem']->disk(
                            $this->app['config']->get('synchronized-fields.filesystem.disk')
                        )
                    );
            }
        });

        $this->app->bind(ObserverContract::class, function () {
            return new SynchronizedFieldsObserver(App::make(StorageContract::class), $this->app['db.connection']);
        });
    }

    /**
     * Register the DynamoDB Client
     */
    protected function registerDynamoClient()
    {
        $this->app->singleton(DynamoDbClient::class, function () {
            return new DynamoDbClient(
                [
                    'endpoint' => $this->app['config']->get('synchronized-fields.dynamo.endpoint'),
                    'region' => $this->app['config']->get('synchronized-fields.dynamo.region'),
                    'version' => $this->app['config']->get('synchronized-fields.dynamo.version'),
                    'credentials' => [
                        'key' => $this->app['config']->get('synchronized-fields.dynamo.credentials.key'),
                        'secret' => $this->app['config']->get('synchronized-fields.dynamo.credentials.secret')
                    ]
                ]
            );
        });
    }
}
