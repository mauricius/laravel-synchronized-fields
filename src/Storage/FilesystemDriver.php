<?php

namespace Mauricius\SynchronizedFields\Storage;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Mauricius\SynchronizedFields\Contracts\StorageContract;

class FilesystemDriver implements StorageContract
{
    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * FilesystemDriver constructor.
     *
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Get the full path of the file for the field.
     *
     * @param  Model  $model
     * @param  string $field
     * @return string
     */
    public function getFullPath(Model $model, string $field): string
    {
        return $this->generateFolderStructure($model) . $this->generateFilename($model, $field);
    }

    /**
     * Generate the filename from the field.
     *
     * @param  Model  $model
     * @param  string $field
     * @return string
     */
    protected function generateFilename(Model $model, string $field): string
    {
        return $model->getKey() . '@' . $field . '.json';
    }

    /**
     * @param  Model $model
     * @return string
     */
    protected function generateFolderStructure(Model $model): string
    {
        $files_per_folder = Config::get('synchronized-fields.filesystem.files_per_folder');

        return
            $model->getTable() .
            DIRECTORY_SEPARATOR .
            implode(DIRECTORY_SEPARATOR, str_split(intval($model->getKey() / $files_per_folder), 2)) .
            DIRECTORY_SEPARATOR;
    }

    /**
     * Make the target folder.
     *
     * @param Model $model
     */
    protected function makeFolder(Model $model): void
    {
        $target = $this->generateFolderStructure($model);

        if (! $this->files->exists($target)) {
            if (! $this->files->makeDirectory($target, 0777, true)) {
                throw new \RuntimeException("Cannot create directory $target.");
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function retrieve(Model $model, array $fields): array
    {
        return Arr::collapse(
            array_map(
                function ($field) use ($model) {
                    try {
                        $path = $this->getFullPath($model, $field);

                        $content = $this->files->get($path);

                        return [$field => $content];
                    } catch (FileNotFoundException $ex) {
                        //
                    }
                },
                $fields
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function persist(Model $model, array $fields): array
    {
        $this->makeFolder($model);

        return array_values(
            array_filter(
                array_map(
                    function ($field) use ($model) {
                        try {
                            $path = $this->getFullPath($model, $field);

                            if (is_null($model->getAttribute($field))) {
                                $this->files->delete($path);

                                return null;
                            }

                            $this->files->put($path, json_encode($model->getAttribute($field)));

                            return $field;
                        } catch (FileNotFoundException $ex) {
                            //
                        }
                    },
                    $fields
                )
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Model $model, array $fields): void
    {
        foreach ($fields as $field) {
            try {
                $this->files->delete(
                    $this->getFullPath($model, $field)
                );
            } catch (FileNotFoundException $ex) {
                //
            }
        }
    }
}
