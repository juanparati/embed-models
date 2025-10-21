<?php

namespace Juanparati\EmbedModels\Casts;

use Juanparati\EmbedModels\EmbedModel;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class AsEmbedModel implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new AsEmbedModelCaster($arguments[0] ?? null);
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

class AsEmbedModelCaster implements CastsAttributes
{
    /**
     * The embedded model class.
     *
     * @var string
     */
    protected string $modelClass;

    /**
     * Create a new cast class instance.
     *
     * @param  string|null  $modelClass
     */
    public function __construct(?string $modelClass = null)
    {
        if (!$modelClass) {
            throw new \InvalidArgumentException('Model class must be specified for AsEmbeddedModel cast.');
        }

        $this->modelClass = $modelClass;
    }

    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return \Juanparati\EmbedModels\EmbedModel|null
     */
    public function get(\Illuminate\Database\Eloquent\Model|\Juanparati\EmbedModels\EmbedModel $model, string $key, mixed $value, array $attributes): ?EmbedModel
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
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return string|null
     */
    public function set(\Illuminate\Database\Eloquent\Model|\Juanparati\EmbedModels\EmbedModel $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if ($value instanceof EmbedModel) {
            return json_encode($value->toArray());
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
