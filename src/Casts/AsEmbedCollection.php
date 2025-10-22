<?php

namespace Juanparati\EmbedModels\Casts;

use \Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Juanparati\EmbedModels\Contracts\EmbedCollectionInterface;
use Juanparati\EmbedModels\Contracts\EmbedModelInterface;
use Juanparati\EmbedModels\EmbedCollection;
use Juanparati\EmbedModels\EmbedModel;


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
     *
     * @param $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     * @return EmbedCollectionInterface|null
     */
    public function get(
        $model,
        string $key,
        mixed $value,
        array $attributes
    ): ?EmbedCollectionInterface
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
     * @param array|EmbedCollectionInterface $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     * @return string|null
     */
    public function set(
        $model,
        string $key,
        mixed $value,
        array $attributes
    ): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if ($value instanceof EmbedCollectionInterface) {
            return json_encode($value->toArray());
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
