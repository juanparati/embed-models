<?php

namespace EloquentEmbedModels\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasAttributes;

trait HasAttributesWithoutModel
{
    use HasAttributes;


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
}
