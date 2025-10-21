<?php

namespace Juanparati\EmbedModels\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasAttributes;

trait HasAttributesWithoutModel
{
    use HasAttributes;


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


    // Override non-applicable methods
    public function relationLoaded($key) {
        return false;
    }

    public function getRelationValue($key) {
        return;
    }

    public function isRelation($key)
    {
        return false;
    }

    public function relationsToArray() : array
    {
        return $this->getAttributes();
    }

    protected function throwMissingAttributeExceptionIfApplicable($key)
    {
        return null;
    }

    protected function handleLazyLoadingViolation($key)
    {
        return;
    }

    protected static function preventsAccessingMissingAttributes()
    {
        return false;
    }

}
