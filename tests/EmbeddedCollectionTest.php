<?php

namespace Juanparati\EmbedModels\Tests;

use Juanparati\EmbedModels\Casts\AsEmbedCollection;
use Juanparati\EmbedModels\Casts\AsEmbedModel;
use Juanparati\EmbedModels\EmbedCollection;
use Juanparati\EmbedModels\EmbedModel;
use Orchestra\Testbench\TestCase;

class EmbeddedCollectionTest extends TestCase
{
    /** @test */
    public function it_can_create_collection_from_array()
    {
        $collection = new TestLineItemCollection([
            ['sku' => 'ABC', 'quantity' => 5],
            ['sku' => 'DEF', 'quantity' => 3],
        ]);

        $this->assertCount(2, $collection);
        $this->assertInstanceOf(TestLineItem::class, $collection[0]);
        $this->assertEquals('ABC', $collection[0]->sku);
    }

    /** @test */
    public function it_can_create_collection_from_json()
    {
        $json = json_encode([
            ['sku' => 'ABC', 'quantity' => 5],
            ['sku' => 'DEF', 'quantity' => 3],
        ]);

        $collection = new TestLineItemCollection($json);

        $this->assertCount(2, $collection);
        $this->assertEquals('ABC', $collection[0]->sku);
    }

    /** @test */
    public function it_supports_array_access()
    {
        $collection = new TestLineItemCollection([
            ['sku' => 'ABC', 'quantity' => 5],
        ]);

        $this->assertEquals('ABC', $collection[0]->sku);
        $this->assertTrue(isset($collection[0]));

        unset($collection[0]);
        $this->assertFalse(isset($collection[0]));
    }

    /** @test */
    public function it_supports_array_append()
    {
        $collection = new TestLineItemCollection;

        $collection[] = new TestLineItem(['sku' => 'ABC', 'quantity' => 5]);
        $collection[] = ['sku' => 'DEF', 'quantity' => 3];

        $this->assertCount(2, $collection);
        $this->assertInstanceOf(TestLineItem::class, $collection[0]);
        $this->assertInstanceOf(TestLineItem::class, $collection[1]);
    }

    /** @test */
    public function it_supports_push_method()
    {
        $collection = new TestLineItemCollection;

        $collection->push(new TestLineItem(['sku' => 'ABC', 'quantity' => 5]));
        $collection->push(['sku' => 'DEF', 'quantity' => 3]);

        $this->assertCount(2, $collection);
        $this->assertEquals('ABC', $collection[0]->sku);
        $this->assertEquals('DEF', $collection[1]->sku);
    }

    /** @test */
    public function it_supports_collection_methods()
    {
        $collection = new TestLineItemCollection([
            ['sku' => 'ABC', 'quantity' => 5],
            ['sku' => 'DEF', 'quantity' => 3],
            ['sku' => 'GHI', 'quantity' => 7],
        ]);

        $first = $collection->first();
        $this->assertEquals('ABC', $first->sku);

        $last = $collection->last();
        $this->assertEquals('GHI', $last->sku);

        $this->assertCount(3, $collection);
        $this->assertFalse($collection->isEmpty());
        $this->assertTrue($collection->isNotEmpty());
    }

    /** @test */
    public function it_supports_filter()
    {
        $collection = new TestLineItemCollection([
            ['sku' => 'ABC', 'quantity' => 5],
            ['sku' => 'DEF', 'quantity' => 3],
            ['sku' => 'GHI', 'quantity' => 7],
        ]);

        $filtered = $collection->filter(function ($item) {
            return $item->quantity > 4;
        });

        $this->assertInstanceOf(TestLineItemCollection::class, $filtered);
        $this->assertCount(2, $filtered);
    }

    /** @test */
    public function it_supports_map()
    {
        $collection = new TestLineItemCollection([
            ['sku' => 'ABC', 'quantity' => 5],
            ['sku' => 'DEF', 'quantity' => 3],
        ]);

        $skus = $collection->map(function ($item) {
            return $item->sku;
        });

        $this->assertEquals(['ABC', 'DEF'], $skus->all());
    }

    /** @test */
    public function it_converts_to_array()
    {
        $collection = new TestLineItemCollection([
            ['sku' => 'ABC', 'quantity' => 5],
            ['sku' => 'DEF', 'quantity' => 3],
        ]);

        $array = $collection->toArray();

        $this->assertEquals([
            ['sku' => 'ABC', 'quantity' => 5],
            ['sku' => 'DEF', 'quantity' => 3],
        ], $array);
    }

    /** @test */
    public function it_converts_to_json()
    {
        $collection = new TestLineItemCollection([
            ['sku' => 'ABC', 'quantity' => 5],
            ['sku' => 'DEF', 'quantity' => 3],
        ]);

        $json = $collection->toJson();

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertCount(2, $decoded);
    }

    /** @test */
    public function it_supports_foreach_iteration()
    {
        $collection = new TestLineItemCollection([
            ['sku' => 'ABC', 'quantity' => 5],
            ['sku' => 'DEF', 'quantity' => 3],
        ]);

        $skus = [];
        foreach ($collection as $item) {
            $skus[] = $item->sku;
        }

        $this->assertEquals(['ABC', 'DEF'], $skus);
    }

    /** @test */
    public function it_supports_put_and_get()
    {
        $collection = new TestLineItemCollection;

        $collection->put('first', ['sku' => 'ABC', 'quantity' => 5]);

        $this->assertTrue($collection->has('first'));
        $this->assertEquals('ABC', $collection->get('first')->sku);
    }

    /** @test */
    public function it_supports_forget()
    {
        $collection = new TestLineItemCollection([
            ['sku' => 'ABC', 'quantity' => 5],
            ['sku' => 'DEF', 'quantity' => 3],
        ]);

        $collection->forget(0);

        $this->assertCount(1, $collection);
    }

    /** @test */
    public function it_handles_empty_collection()
    {
        $collection = new TestLineItemCollection;

        $this->assertCount(0, $collection);
        $this->assertTrue($collection->isEmpty());
        $this->assertEquals([], $collection->toArray());
    }

    /** @test */
    public function it_proxies_collection_methods()
    {
        $collection = new TestLineItemCollection([
            ['sku' => 'ABC', 'quantity' => 5],
            ['sku' => 'DEF', 'quantity' => 3],
        ]);

        // Test pluck method (proxied to underlying collection)
        $quantities = $collection->pluck('quantity');

        $this->assertEquals([5, 3], $quantities->all());
    }

    /** @test */
    public function it_automatically_converts_items_to_models()
    {
        $collection = new TestLineItemCollection;

        // Add array
        $collection->push(['sku' => 'ABC', 'quantity' => 5]);

        // Add model
        $collection->push(new TestLineItem(['sku' => 'DEF', 'quantity' => 3]));

        $this->assertInstanceOf(TestLineItem::class, $collection[0]);
        $this->assertInstanceOf(TestLineItem::class, $collection[1]);
    }

    /** @test */
    public function it_can_cast_collection_automatically()
    {
        $model = new TestMainModel(['line_items' => [
            ['sku' => 'ABC', 'quantity' => 5],
            ['sku' => 'DEF', 'quantity' => 3],
        ]]);

        $this->assertInstanceOf(TestLineItem::class, $model->line_items[0]);
        $this->assertEquals('ABC', $model->line_items[0]->sku);
    }

}

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
