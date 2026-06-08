<?php

namespace Juanparati\EmbedModels\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\DateFormat;
use Illuminate\Database\Eloquent\Concerns\HasAttributes;

trait HasAttributesWithoutModel
{
    use HasAttributes {
        initializeHasAttributes as initializeHasAttributesBase;
    }

    /**
     * Cache for resolved PHP class attributes.
     *
     * @var array<string, mixed>
     */
    protected static array $classAttributes = [];

    /**
     * Resolve a PHP attribute from the class hierarchy.
     *
     * This mirrors Eloquent\Model::resolveClassAttribute() so that
     * HasAttributes and HidesAttributes can read PHP 8 attributes
     * like #[Appends], #[Hidden], #[Visible], and #[DateFormat].
     */
    protected static function resolveClassAttribute(string $attributeClass, ?string $property = null, ?string $class = null): mixed
    {
        $class ??= static::class;

        $cacheKey = $class.'@'.$attributeClass;

        if (array_key_exists($cacheKey, static::$classAttributes)) {
            return static::$classAttributes[$cacheKey];
        }

        try {
            $reflection = new \ReflectionClass($class);

            do {
                $attributes = $reflection->getAttributes($attributeClass);

                if (count($attributes) > 0) {
                    $instance = $attributes[0]->newInstance();

                    return static::$classAttributes[$cacheKey] = $property ? $instance->{$property} : $instance;
                }
            } while ($reflection = $reflection->getParentClass());
        } catch (\Exception) {
            //
        }

        return static::$classAttributes[$cacheKey] = null;
    }

    /**
     * Override initializeHasAttributes to handle the dateFormat resolution
     * without relying on the Table attribute (which is Eloquent-specific).
     */
    protected function initializeHasAttributes()
    {
        $this->casts = $this->ensureCastsAreStringValues(
            array_merge($this->casts, $this->casts()),
        );

        $this->dateFormat ??= static::resolveClassAttribute(DateFormat::class, 'format');

        $this->mergeAppends(static::resolveClassAttribute(Appends::class, 'columns') ?? []);
    }

    /**
     * Return a timestamp as DateTime object.
     *
     * @param  mixed  $value
     */
    protected function asDateTime($value): CarbonInterface
    {
        return \Illuminate\Support\Carbon::parse($value);
    }

    // Override non-applicable methods
    public function relationLoaded($key)
    {
        return false;
    }

    public function getRelationValue($key) {}

    public function isRelation($key)
    {
        return false;
    }

    public function relationsToArray(): array
    {
        return $this->getAttributes();
    }

    protected function throwMissingAttributeExceptionIfApplicable($key)
    {
        return null;
    }

    protected function handleLazyLoadingViolation($key) {}

    protected static function preventsAccessingMissingAttributes()
    {
        return false;
    }
}
