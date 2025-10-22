<?php

namespace Juanparati\EmbedModels\Casts;

use \Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Juanparati\EmbedModels\Contracts\EmbedModelInterface;
use Juanparati\EmbedModels\EmbedModel;


class AsEmbedModel implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new AsEmbedModelCaster($arguments[0] ?? null);
    }

    /**
     * Factory method for fluent cast syntax.
     */
    public static function of(string $class): string
    {
        return static::class.':'.$class;
    }
}

class AsEmbedModelCaster implements CastsAttributes
{
    /**
     * The embedded model class.
     */
    protected string $modelClass;

    /**
     * Create a new cast class instance.
     */
    public function __construct(?string $modelClass = null)
    {
        if (! $modelClass) {
            throw new \InvalidArgumentException('Model class must be specified for AsEmbeddedModel cast.');
        }

        $this->modelClass = $modelClass;
    }

    /**
     * Cast the given value.
     *
     * @param Model|EmbedModelInterface $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     * @return EmbedModel|null
     */
    public function get(
        $model,
        string $key,
        mixed $value,
        array $attributes
    ): ?EmbedModelInterface
    {
        if (is_null($value)) {
            return null;
        }

        $data = is_string($value) ? json_decode($value, true) : $value;

        if (! is_array($data)) {
            return null;
        }

        return new $this->modelClass($data);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param Model|EmbedModelInterface $model
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

        if ($value instanceof EmbedModelInterface) {
            return json_encode($value->toArray());
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
