<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Here you can completely disabled the fields synchronization.
    |
    */

    'enabled' => env('SYNCHRONIZED_FIELDS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Storage Driver
    |--------------------------------------------------------------------------
    |
    | The driver that will be used to synchronized fields
    | against your preferred storage mechanism.
    |
    | Supported: "filesystem", "dynamo", "database"
    */

    'driver' => env('SYNCHRONIZED_FIELDS_DRIVER', 'filesystem'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Driver settings
    |--------------------------------------------------------------------------
    |
    | This driver persists your fields as JSON files in one of your
    | filesystems you've configured in config/filesystems.php.
    | You can also define how many files will
    | be persisted for each folder.
    */

    'filesystem' => [
        'disk' => 'local',
        'files_per_folder' => 1000
    ],

    /*
    |--------------------------------------------------------------------------
    | DynamoDB Driver settings
    |--------------------------------------------------------------------------
    |
    | Here you can set the options for connecting to a DynamoDB instance.
    */

    'dynamo' => [
        'endpoint' => 'http://localhost:8000',
        'region' => 'us-east-1',
        'version' => 'latest',
        'credentials' => [
            'key' => '',
            'secret' => ''
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which database connections you wish to use
    | to synchronize fields among all the connections defined in Laravel.
    |
    */

    'database' => [
        'connection' => env('SYNCHRONIZED_FIELDS_DB_CONNECTION', 'mysql')
    ],

    /*
    |--------------------------------------------------------------------------
    | Replicate fields
    |--------------------------------------------------------------------------
    |
    | Here you can decide if you want to replicate your fields with
    | the storage mechanism and keep the original fields intact,
    | or if you want to nullify them after the synchronization.
    |
    */

    'replicate' => env('SYNCHRONIZED_FIELDS_REPLICATE', true)
];
