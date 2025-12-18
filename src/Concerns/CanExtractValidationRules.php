<?php

namespace Juanparati\EmbedModels\Concerns;

trait CanExtractValidationRules
{
    /**
     * Facade that allows to extract the validation rules from the embed model.
     */
    public static function extractValidationRules(): array {
        return (new static)->validationRules();
    }


    /**
     * Encapsulate the validation rules into a parent rule.
     */
    public static function encapsulateRules(
        string       $into,
        array|string $parentRule = 'nullable|array',
        bool         $isCollection = false,
    ): array
    {
        return [
            $into => $parentRule,
            ...\Arr::mapWithKeys(
                static::extractValidationRules(),
                fn($v, $k) => ["$into" . ($isCollection ? '.*' : '') . ".$k" => $v]
            )
        ];
    }


}
