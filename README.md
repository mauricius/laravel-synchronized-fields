# Laravel Synchronized Fields

This package targets a very specific need, which is moving long and storage-expensive JSON fields out of the main database to external storage systems, without sacrificing the simplicity and the comfort of Eloquent models.

It has some limitations, though:

* It works only with Eloquent Models with numeric PKs
* It does not work with relationships. You cannot use it to persist JSON fields in pivot tables, unless you define a custom Model for them.
* You will have to set your JSON fields as `nullable`

## Requirements

This package requires Laravel 5.5 or higher, PHP 7.0 or higher.

## Documentation

### Installation

The package can be installed via composer:

```
composer require mauricius/laravel-synchronized-fields
```

The package will automatically register itself.

### Configuration

You may publish the configuration file, which will be located at `config/synchronized-fields.php`, using the following command

```
php artisan vendor:publish --provider="Mauricius\SynchronizedFields\SynchronizedFieldsServiceProvider"
```

#### Disabling SynchronizedFields

If you wish to disable the package entirely, you may set the `enabled` key in `config/synchronized-fields.php` to `false`.

#### Set the Storage driver

You can set the storage driver that will be used to store JSON fields using the `driver` key in `config/synchronized-fields.php`. Available values are:

* `filesystem` (default)
* `dynamo`
* `database`

##### Filesystem

The `filesystem` driver stores JSON fields in one of the [Laravel filesystems](https://laravel.com/docs/6.x/filesystem#configuration) defined in `config/filesystem.php` configuration file. You can set the name of the `disk` instance in the `filesystem.disk` key. In order to simplify management files are splitted between multiple folders, so you may define how many files per folder you want to store using the `files_per_folder` key.

**Note**: make sure to not change this value once you started using this package.

##### DynamoDB

The `dynamo` driver stores fields in AWS DynamoDB. You need to fill all the required settings in order to connect to the DynamoDB instance.
 
**Note:** you need to create the tables yourself.

##### Database

The `database` driver lets you specify an existing database connection from the list of [Laravel connections](https://laravel.com/docs/6.x/database#configuration) defined in the `database.php` configuration file. For example you can store fields in a SQLite Database or in a different MySQL database. 

**Note:** you will have to create tables yourself. Just make sure that tables matches the original structure, except of course for the fields that you don't want to synchronize.

#### Replicate

If you just want to store a copy of each JSON field, instead of removing it from the original source you need to set the `replicate` key to `true`.

### Usage

Simply use the `FieldSynchronizer` trait in the models that you want to synchronize and set the static property for the fields that you want to synchronize.

```php
use Mauricius\SynchronizedFields\Traits\SynchronizedFields;

class Post extends Model
{
    use SynchronizedFields;

    /**
     * The attributes that should be synchronized.
     *
     * @var array
     */
    protected static $synchronizedFields = [
        'metadata'
    ];
}
```
And  then use it like this:

```php
$post = new Post();
$post->title = ...

$post->metadata = [
    'key' => 'value'
    ...
];

$post->save();
```

The `metadata` field will be synchronized behind the scenes after saving the model.

The same works when retrieving the model:

```php
$post = Post::find(1);

// if $post->metadata is null in the DB
// the value will be fetched from the storage

dump($post->metadata);

// [
//     'key' => 'value'
//     ...
// ];
```

#### Skip synchronized fields

If you want to ignore synchronized fields when using the model you can use the `withoutSynchronizedFields` method.

```php
$post = Post::withoutSynchronizedFields(function () {
    return Post::find(1);
});
```

#### Skip specific synchronized fields

If you want instead to ignore only specific synchronized fields when using the model you can use the `ignoringSynchronizedFields` method.

```php
$post = Post::ignoringSynchronizedFields(['metadata'], function () {
    return Post::find(1);
});
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
