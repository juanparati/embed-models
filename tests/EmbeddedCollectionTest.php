<?php

use Juanparati\EmbedModels\Casts\AsEmbedCollection;
use Juanparati\EmbedModels\EmbedCollection;
use Juanparati\EmbedModels\EmbedModel;

it('can create collection from array', function () {
    $collection = new TestLineItemCollection([
        ['sku' => 'ABC', 'quantity' => 5],
        ['sku' => 'DEF', 'quantity' => 3],
    ]);

    expect($collection)->toHaveCount(2);
    expect($collection[0])->toBeInstanceOf(TestLineItem::class);
    expect($collection[0]->sku)->toBe('ABC');
});

it('can create collection from json', function () {
    $json = json_encode([
        ['sku' => 'ABC', 'quantity' => 5],
        ['sku' => 'DEF', 'quantity' => 3],
    ]);

    $collection = new TestLineItemCollection($json);

    expect($collection)->toHaveCount(2);
    expect($collection[0]->sku)->toBe('ABC');
});

it('supports array access', function () {
    $collection = new TestLineItemCollection([
        ['sku' => 'ABC', 'quantity' => 5],
    ]);

    expect($collection[0]->sku)->toBe('ABC');
    expect(isset($collection[0]))->toBeTrue();

    unset($collection[0]);
    expect(isset($collection[0]))->toBeFalse();
});

it('supports array append', function () {
    $collection = new TestLineItemCollection;

    $collection[] = new TestLineItem(['sku' => 'ABC', 'quantity' => 5]);
    $collection[] = ['sku' => 'DEF', 'quantity' => 3];

    expect($collection)->toHaveCount(2);
    expect($collection[0])->toBeInstanceOf(TestLineItem::class);
    expect($collection[1])->toBeInstanceOf(TestLineItem::class);
});

it('supports push method', function () {
    $collection = new TestLineItemCollection;

    $collection->push(new TestLineItem(['sku' => 'ABC', 'quantity' => 5]));
    $collection->push(['sku' => 'DEF', 'quantity' => 3]);

    expect($collection)->toHaveCount(2);
    expect($collection[0]->sku)->toBe('ABC');
    expect($collection[1]->sku)->toBe('DEF');
});

it('supports collection methods', function () {
    $collection = new TestLineItemCollection([
        ['sku' => 'ABC', 'quantity' => 5],
        ['sku' => 'DEF', 'quantity' => 3],
        ['sku' => 'GHI', 'quantity' => 7],
    ]);

    $first = $collection->first();
    expect($first->sku)->toBe('ABC');

    $last = $collection->last();
    expect($last->sku)->toBe('GHI');

    expect($collection)->toHaveCount(3);
    expect($collection->isEmpty())->toBeFalse();
    expect($collection->isNotEmpty())->toBeTrue();
});

it('supports filter', function () {
    $collection = new TestLineItemCollection([
        ['sku' => 'ABC', 'quantity' => 5],
        ['sku' => 'DEF', 'quantity' => 3],
        ['sku' => 'GHI', 'quantity' => 7],
    ]);

    $filtered = $collection->filter(function ($item) {
        return $item->quantity > 4;
    });

    expect($filtered)->toBeInstanceOf(TestLineItemCollection::class);
    expect($filtered)->toHaveCount(2);
});

it('supports map', function () {
    $collection = new TestLineItemCollection([
        ['sku' => 'ABC', 'quantity' => 5],
        ['sku' => 'DEF', 'quantity' => 3],
    ]);

    $skus = $collection->map(function ($item) {
        return $item->sku;
    });

    expect($skus->all())->toBe(['ABC', 'DEF']);
});

it('converts to array', function () {
    $collection = new TestLineItemCollection([
        ['sku' => 'ABC', 'quantity' => 5],
        ['sku' => 'DEF', 'quantity' => 3],
    ]);

    $array = $collection->toArray();

    expect($array)->toBe([
        ['sku' => 'ABC', 'quantity' => 5],
        ['sku' => 'DEF', 'quantity' => 3],
    ]);
});

it('converts to json', function () {
    $collection = new TestLineItemCollection([
        ['sku' => 'ABC', 'quantity' => 5],
        ['sku' => 'DEF', 'quantity' => 3],
    ]);

    $json = $collection->toJson();

    expect($json)->toBeJson();
    $decoded = json_decode($json, true);
    expect($decoded)->toHaveCount(2);
});

it('supports foreach iteration', function () {
    $collection = new TestLineItemCollection([
        ['sku' => 'ABC', 'quantity' => 5],
        ['sku' => 'DEF', 'quantity' => 3],
    ]);

    $skus = [];
    foreach ($collection as $item) {
        $skus[] = $item->sku;
    }

    expect($skus)->toBe(['ABC', 'DEF']);
});

it('supports put and get', function () {
    $collection = new TestLineItemCollection;

    $collection->put('first', ['sku' => 'ABC', 'quantity' => 5]);

    expect($collection->has('first'))->toBeTrue();
    expect($collection->get('first')->sku)->toBe('ABC');
});

it('supports forget', function () {
    $collection = new TestLineItemCollection([
        ['sku' => 'ABC', 'quantity' => 5],
        ['sku' => 'DEF', 'quantity' => 3],
    ]);

    $collection->forget(0);

    expect($collection)->toHaveCount(1);
});

it('handles empty collection', function () {
    $collection = new TestLineItemCollection;

    expect($collection)->toHaveCount(0);
    expect($collection->isEmpty())->toBeTrue();
    expect($collection->toArray())->toBe([]);
});

it('proxies collection methods', function () {
    $collection = new TestLineItemCollection([
        ['sku' => 'ABC', 'quantity' => 5],
        ['sku' => 'DEF', 'quantity' => 3],
    ]);

    // Test pluck method (proxied to underlying collection)
    $quantities = $collection->pluck('quantity');

    expect($quantities->all())->toBe([5, 3]);
});

it('automatically converts items to models', function () {
    $collection = new TestLineItemCollection;

    // Add array
    $collection->push(['sku' => 'ABC', 'quantity' => 5]);

    // Add model
    $collection->push(new TestLineItem(['sku' => 'DEF', 'quantity' => 3]));

    expect($collection[0])->toBeInstanceOf(TestLineItem::class);
    expect($collection[1])->toBeInstanceOf(TestLineItem::class);
});

it('can cast collection automatically', function () {
    $model = new TestMainModel(['line_items' => [
        ['sku' => 'ABC', 'quantity' => 5],
        ['sku' => 'DEF', 'quantity' => 3],
    ]]);

    expect($model->line_items[0])->toBeInstanceOf(TestLineItem::class);
    expect($model->line_items[0]->sku)->toBe('ABC');
});

// Test classes

class TestLineItem extends EmbedModel
{
    //
}

class TestLineItemCollection extends EmbedCollection
{
    protected function getDefaultModelClass(): string
    {
        return TestLineItem::class;
    }
}

class TestMainModel extends EmbedModel
{
    protected function casts()
    {
        return [
            'line_items' => AsEmbedCollection::of(TestLineItemCollection::class),
        ];
    }
}
