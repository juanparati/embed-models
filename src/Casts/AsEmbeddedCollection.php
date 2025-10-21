<?php

namespace EloquentEmbedModels\Casts;

use EloquentEmbedModels\EmbeddedCollection;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class AsEmbeddedCollection implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new AsEmbeddedCollectionCaster($arguments[0] ?? null);
    }

    /**
     * Factory method for fluent cast syntax.
     *
     * @param string $class
     * @return string
     */
    public static function of(string $class): string
    {
        return static::class . ':' . $class;
    }
}

class AsEmbeddedCollectionCaster implements CastsAttributes
{
    /**
     * The embedded collection class.
     *
     * @var string
     */
    protected string $collectionClass;

    /**
     * Create a new cast class instance.
     *
     * @param  string|null  $collectionClass
     */
    public function __construct(?string $collectionClass = null)
    {
        if (!$collectionClass) {
            throw new \InvalidArgumentException('Collection class must be specified for AsEmbeddedCollection cast.');
        }

        $this->collectionClass = $collectionClass;
    }

    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return \EloquentEmbedModels\EmbeddedCollection|null
     */
    public function get(\Illuminate\Database\Eloquent\Model $model, string $key, mixed $value, array $attributes): ?EmbeddedCollection
    {
        if (is_null($value)) {
            return null;
        }

        $data = is_string($value) ? json_decode($value, true) : $value;

        if (! is_array($data)) {
            return null;
        }

        return new $this->collectionClass($data);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return string|null
     */
    public function set(\Illuminate\Database\Eloquent\Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if ($value instanceof EmbeddedCollection) {
            return json_encode($value->toArray());
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
