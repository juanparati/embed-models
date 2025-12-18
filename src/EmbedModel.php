<?php

namespace Juanparati\EmbedModels;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Concerns\HidesAttributes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Fluent;
use Illuminate\Validation\ValidationException;
use JsonSerializable;
use Juanparati\EmbedModels\Concerns\HasAttributesWithoutModel;
use Juanparati\EmbedModels\Contracts\EmbedModelInterface;

abstract class EmbedModel extends Fluent implements EmbedModelInterface
{
    use HasAttributesWithoutModel {
        castAttribute as castAttributeOrig;
    }
    use HidesAttributes;

    /**
     * The cached cast types.
     *
     * @var array<string, string>|null
     */
    protected ?array $cachedCasts = null;

    /**
     * The attributes that aren't mass assignable'.
     *
     * @var string[]
     */
    protected $guarded = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array|string[]
     */
    protected $fillable = ['*'];

    /**
     * Indicates if all mass assignment is enabled.
     */
    protected static bool $unguarded = false;

    public function __construct($attributes = [])
    {
        $this->initializeHasAttributes();
        parent::__construct($attributes);
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param  iterable  $attributes
     * @return $this
     */
    public function fill($attributes)
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    public function isFillable($key): bool
    {
        if (static::$unguarded) {
            return true;
        }

        if ($this->guarded === ['*'] || in_array($key, $this->guarded)) {
            return false;
        }

        if ($this->fillable === ['*'] || in_array($key, $this->fillable)) {
            return true;
        }

        return false;
    }

    /**
     * Set an attribute on the embedded model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        // First we will check for the presence of a mutator for the set operation
        // which simply lets the developers tweak the attribute as it is set on
        // this model, such as "json_encoding" a listing of data for storage.
        if ($this->hasSetMutator($key)) {
            return $this->setMutatedAttributeValue($key, $value);
        } elseif ($this->hasAttributeSetMutator($key)) {
            return $this->setAttributeMarkedMutatedAttributeValue($key, $value);
        }

        if ($this->isEnumCastable($key)) {
            $this->setEnumCastableAttribute($key, $value);

            return $this;
        }

        if (! is_null($value) && $this->isEncryptedCastable($key)) {
            $value = $this->castAttributeAsEncryptedString($key, $value);
        }

        if (! is_null($value) && $this->hasCast($key, 'hashed')) {
            $value = $this->castAttributeAsHashedString($key, $value);
        }

        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Get an attribute from the embedded model.
     *
     * @param  string  $key
     */
    public function getAttribute($key): mixed
    {
        if (! $key) {
            return null;
        }

        // Check if the attribute exists or has an accessor
        if (array_key_exists($key, $this->attributes) || $this->hasGetMutator($key) || $this->hasAttributeGetMutator($key)) {
            return $this->getAttributeValue($key);
        }

        return null;
    }

    /**
     * Determine whether an attribute should be cast to a native type.
     *
     * @param  string  $key
     * @return bool
     */
    protected function getCasts(): array
    {
        if ($this->cachedCasts !== null) {
            return $this->cachedCasts;
        }

        // Check if there's a casts() method
        if (method_exists($this, 'casts')) {
            $this->cachedCasts = array_merge($this->casts, $this->casts());
        } else {
            $this->cachedCasts = $this->casts;
        }

        return $this->cachedCasts;
    }

    /**
     * Cast an attribute to a native PHP type.
     *
     * @param  string  $key
     * @param  mixed  $value
     */
    protected function castAttribute($key, $value): mixed
    {
        $newValue = $this->castAttributeAsClass($key, $value, $this->getCastType($key));

        if ($newValue !== null) {
            return $newValue;
        }

        // Handle basic types that don't need Laravel's cast system
        switch ($this->getCastType($key)) {
            case 'array':
            case 'json':
                return is_string($value) ? json_decode($value, true) : (array) $value;
            case 'object':
                return is_string($value) ? json_decode($value) : (object) $value;
            case 'collection':
                $data = is_string($value) ? json_decode($value, true) : $value;

                return collect($data);
        }

        return $this->castAttributeOrig($key, $value);
    }

    /**
     * Cast an attribute to a custom class.
     */
    protected function castAttributeAsClass(string $key, mixed $value, string $castType): mixed
    {
        // Handle nested EmbeddedModel
        if (is_subclass_of($castType, EmbedModel::class)) {
            if (is_array($value)) {
                return new $castType($value);
            }
            if (is_string($value)) {
                return new $castType(json_decode($value, true));
            }

            return $value;
        }

        // Handle EmbeddedCollection
        if (is_subclass_of($castType, EmbedCollection::class)) {
            return new $castType($value);
        }

        return null;
    }


    /**
     * Get the validation rules that apply to the embedded model.
     *
     * @return array
     */
    public function validationRules()
    {
        return [];
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        $attributes = [];

        foreach ($this->attributes as $key => $value) {
            $attributes[$key] = $this->getArrayableValue($value);
        }

        // Append accessors
        foreach ($this->getArrayableAppends() as $key) {
            $attributes[$key] = $this->getArrayableValue($this->getAttribute($key));
        }

        return $attributes;
    }

    /**
     * Get the accessors that are being appended to arrays.
     *
     * @return array
     */
    protected function getArrayableAppends()
    {
        if (!isset($this->appends)) {
            return [];
        }

        return $this->appends;
    }

    /**
     * Get an arrayable value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function getArrayableValue($value)
    {
        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        if ($value instanceof JsonSerializable) {
            return $value->jsonSerialize();
        }

        return $value;
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the object into something JSON serializable.
     */
    public function jsonSerialize(): array
    {
        $data = $this->toArray();

        if (!empty($this->validationRules())) {
            $validator = Validator::make(
                $data,
                $this->validationRules()
            );

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        }

        return $data;
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if an attribute exists on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return ! is_null($this->getAttribute($key));
    }

    /**
     * Unset an attribute on the model.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }
}
