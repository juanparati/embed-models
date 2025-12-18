<?php

namespace Juanparati\EmbedModels\Concerns;

trait CanExtractValidationRules
{
    public string $validationInto = '';

    /**
     * Facade that allows to extract the validation rules from the embed model.
     */
    public static function extractValidationRules(string $into = ''): array {
        $model = new static;
        $model->validationInto = $into;
        return $model->validationRules();
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
                static::extractValidationRules($into ? ($into . ($isCollection ? '.*.' : '.')) : ''),
                fn($v, $k) => [$into . ($isCollection ? '.*' : '') . ".$k" => $v]
            )
        ];
    }
}
