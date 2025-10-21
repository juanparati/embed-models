<?php

namespace EloquentEmbedModels;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Concerns\HidesAttributes;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use JsonSerializable;
use EloquentEmbedModels\Concerns\HasAttributesWithoutModel;

abstract class EmbeddedModel extends Fluent
{
    use HidesAttributes;
    use HasAttributesWithoutModel {
        castAttribute as castAttributeOrig;
    }


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
    protected array $guarded = [];


    /**
     * The attributes that are mass assignable.
     *
     * @var array|string[]
     */
    protected array $fillable = ['*'];


    /**
     * Indicates if all mass assignment is enabled.
     *
     * @var bool
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
     * @param iterable $attributes
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
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setAttribute($key, $value): static
    {
        // Run validation before setting
        $this->validateAttribute($key, $value);

        // Handle mutators
        if ($this->hasSetMutator($key)) {
            return $this->setMutatedAttributeValue($key, $value);
        }

        // Handle casting
        if ($this->hasCast($key)) {
            $value = $this->castAttribute($key, $value);
        }

        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Get an attribute from the embedded model.
     *
     * @param string $key
     * @return mixed
     */
    public function getAttribute($key): mixed
    {
        if (!$key) {
            return null;
        }

        // Check if attribute exists
        if (array_key_exists($key, $this->attributes) || $this->hasGetMutator($key)) {
            return $this->getAttributeValue($key);
        }

        return null;
    }

    /**
     * Get a plain attribute (not a relationship).
     *
     * @param string $key
     * @return mixed
     */
    protected function getAttributeValue($key): mixed
    {
        // Handle mutators
        if ($this->hasGetMutator($key)) {
            return $this->mutateAttribute($key, $this->attributes[$key] ?? null);
        }

        $value = $this->attributes[$key] ?? null;

        // Handle casting
        if ($this->hasCast($key)) {
            return $this->castAttribute($key, $value);
        }

        return $value;
    }

    /**
     * Determine if a get mutator exists for an attribute.
     *
     * @param string $key
     * @return bool
     */
    public function hasGetMutator($key): bool
    {
        return method_exists($this, 'get' . Str::studly($key) . 'Attribute');
    }

    /**
     * Determine if a set mutator exists for an attribute.
     *
     * @param string $key
     * @return bool
     */
    public function hasSetMutator($key): bool
    {
        return method_exists($this, 'set' . Str::studly($key) . 'Attribute');
    }

    /**
     * Get the value of an attribute using its mutator.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    protected function mutateAttribute($key, $value): mixed
    {
        return $this->{'get' . Str::studly($key) . 'Attribute'}($value);
    }

    /**
     * Set the value of an attribute using its mutator.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    protected function setMutatedAttributeValue($key, $value): mixed
    {
        $this->attributes[$key] = $this->{'set' . Str::studly($key) . 'Attribute'}($value);
        return $this;
    }

    /**
     * Determine whether an attribute should be cast to a native type.
     *
     * @param string $key
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
     * @param string $key
     * @param mixed $value
     * @return mixed
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
                return is_string($value) ? json_decode($value, true) : (array)$value;
            case 'object':
                return is_string($value) ? json_decode($value) : (object)$value;
            case 'collection':
                $data = is_string($value) ? json_decode($value, true) : $value;
                return collect($data);
        }

        return $this->castAttributeOrig($key, $value);
    }

    /**
     * Get the type of cast for a model attribute.
     *
     * @param string $key
     * @return string
     */

    protected function getCastType($key): string
    {
        $casts = $this->getCasts();
        $castType = $casts[$key];

        // Handle parameterized casting (e.g., "decimal:2")
        if (str_contains($castType, ':')) {
            return explode(':', $castType, 2)[0];
        }

        return $castType;
    }

    /**
     * Cast an attribute to a custom class.
     *
     * @param string $key
     * @param mixed $value
     * @param string $castType
     * @return mixed
     */
    protected function castAttributeAsClass(string $key, mixed $value, string $castType): mixed
    {
        // Handle nested EmbeddedModel
        if (is_subclass_of($castType, EmbeddedModel::class)) {
            if (is_array($value)) {
                return new $castType($value);
            }
            if (is_string($value)) {
                return new $castType(json_decode($value, true));
            }
            return $value;
        }

        // Handle EmbeddedCollection
        if (is_subclass_of($castType, EmbeddedCollection::class)) {
            return new $castType($value);
        }

        return null;
    }

    /**
     * Return a timestamp as DateTime object.
     *
     * @param mixed $value
     * @return CarbonInterface
     */
    protected function asDateTime($value): CarbonInterface
    {
        return \Illuminate\Support\Carbon::parse($value);
    }

    /**
     * Return a timestamp as unix timestamp.
     *
     * @param mixed $value
     * @return int
     */
    protected function asTimestamp($value): int
    {
        return $this->asDateTime($value)->getTimestamp();
    }

    /**
     * Validate an attribute before setting it.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateAttribute(string $key, mixed $value): void
    {
        $rules = $this->rules();

        if (empty($rules) || !array_key_exists($key, $rules)) {
            return;
        }

        $validator = Validator::make(
            [$key => $value],
            [$key => $rules[$key]]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Get the validation rules that apply to the embedded model.
     *
     * @return array
     */
    protected function rules()
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

        return $attributes;
    }

    /**
     * Get an arrayable value.
     *
     * @param mixed $value
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
     * @param int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if an attribute exists on the model.
     *
     * @param string $key
     * @return bool
     */
    public function __isset($key)
    {
        return !is_null($this->getAttribute($key));
    }

    /**
     * Unset an attribute on the model.
     *
     * @param string $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }
}
