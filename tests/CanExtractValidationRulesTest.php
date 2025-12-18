<?php

use Juanparati\EmbedModels\EmbedModel;

it('can extract validation rules from class', function () {
    expect(TestWithValidation::extractValidationRules())
        ->toBe((new TestWithValidation())->validationRules());
});

it('can encapsulate validation rules', function () {
    expect(TestWithValidation::encapsulateRules('test'))
        ->toBe(['test' => 'nullable|array', 'test.num' => 'required|int']);
});

it('can encapsulate validation rules with parent rules', function () {
    expect(TestWithValidation::encapsulateRules('test', 'nullable'))
        ->toBe(['test' => 'nullable|array', 'test.num' => 'required|int']);
});

it('can encapsulate validation rules with parent rules as collection', function () {
    expect(TestWithValidation::encapsulateRules('test', 'nullable', true))
        ->toBe(['test' => 'nullable', 'test.*.num' => 'required|int']);
});

// Test classes
class TestWithValidation extends EmbedModel
{
    use \Juanparati\EmbedModels\Concerns\CanExtractValidationRules;

    public function validationRules(): array
    {
        return [
            'num' => 'required|int',
        ];
    }
}

