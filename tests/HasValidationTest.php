<?php

use Juanparati\EmbedModels\EmbedModel;

it('can extract validation rules from class', function () {
    expect(TestWithValidation::extractValidationRules())
        ->toBe((new TestWithValidation)->validationRules());
});

it('can encapsulate validation rules', function () {
    expect(TestWithValidation::encapsulateRules('test'))
        ->toBe(['test' => 'nullable|array', 'test.num' => 'required|int', 'test.id' => 'required_if:test.num,1']);
});

it('can encapsulate validation rules with parent rules', function () {
    expect(TestWithValidation::encapsulateRules('test', 'nullable'))
        ->toBe(['test' => 'nullable', 'test.num' => 'required|int', 'test.id' => 'required_if:test.num,1']);
});

it('can encapsulate validation rules with parent rules as collection', function () {
    expect(TestWithValidation::encapsulateRules('test', 'nullable', true))
        ->toBe(['test' => 'nullable', 'test.*.num' => 'required|int', 'test.*.id' => 'required_if:test.*.num,1']);
});

it('can validate', function () {
    expect(TestWithValidation::validateRules(['num' => 1]))->toBeFalse();
});

// Test classes
class TestWithValidation extends EmbedModel
{
    use \Juanparati\EmbedModels\Concerns\HasValidation;

    public function validationRules(string $into = '', array $input = []): array
    {
        return [
            'num' => 'required|int',
            'id' => 'required_if:'.$into.'num,1',
        ];
    }
}
