<?php

namespace Juanparati\EmbedModels\Concerns;

use Juanparati\EmbedModels\Contracts\EmbedModelInterface;

trait HasValidation
{

    /**
     * Return the validation rules used for the model.
     *
     * @param string $into Parent (Use for nested rules)
     * @param array $input Input data
     * @return array
     */
    abstract public function validationRules(string $into = '', array $input = []): array;


    /**
     * Facade that allows to extract the validation rules from the embed model.
     */
    public static function extractValidationRules(string $into = '', array $input = []): array {
        $model = new static;
        return $model->validationRules($into, $input);
    }


    /**
     * Encapsulate the validation rules into a parent rule.
     */
    public static function encapsulateRules(
        string       $into,
        array|string $parentRule = 'nullable|array',
        bool         $isCollection = false,
        array        $input = []
    ): array
    {
        return [
            $into => $parentRule,
            ...\Arr::mapWithKeys(
                static::extractValidationRules($into ? ($into . ($isCollection ? '.*.' : '.')) : '', $input),
                fn($v, $k) => [$into . ($isCollection ? '.*' : '') . ".$k" => $v]
            )
        ];
    }


    /**
     * Validate attributes
     */
    public static function validateRules(array|EmbedModelInterface $data): bool {
        $model = new static;
        $data = $data instanceof EmbedModelInterface ? $data->toArray() : $data;
        return \Validator::make(
            $data,
            $model->validationRules('', $data instanceof EmbedModelInterface ? $data->toArray() : $data)
        )->passes();
    }

}
