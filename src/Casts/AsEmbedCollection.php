<?php

namespace Juanparati\EmbedModels\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Juanparati\EmbedModels\EmbedCollection;

class AsEmbedCollection implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new AsEmbedCollectionCaster($arguments[0] ?? null);
    }

    /**
     * Factory method for fluent cast syntax.
     */
    public static function of(string $class): string
    {
        return static::class.':'.$class;
    }
}

class AsEmbedCollectionCaster implements CastsAttributes
{
    /**
     * The embedded collection class.
     */
    protected string $collectionClass;

    /**
     * Create a new cast class instance.
     */
    public function __construct(?string $collectionClass = null)
    {
        if (! $collectionClass) {
            throw new \InvalidArgumentException('Collection class must be specified for AsEmbedCollection cast.');
        }

        $this->collectionClass = $collectionClass;
    }

    /**
     * Cast the given value.
     */
    public function get(\Illuminate\Database\Eloquent\Model $model, string $key, mixed $value, array $attributes): ?EmbedCollection
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
     */
    public function set(\Illuminate\Database\Eloquent\Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if ($value instanceof EmbedCollection) {
            return json_encode($value->toArray());
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
