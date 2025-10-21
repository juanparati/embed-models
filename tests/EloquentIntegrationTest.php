<?php

namespace EloquentEmbedModels\Tests;

use EloquentEmbedModels\Casts\AsEmbeddedModel;
use EloquentEmbedModels\Casts\AsEmbeddedCollection;
use EloquentEmbedModels\EmbeddedModel;
use EloquentEmbedModels\EmbeddedCollection;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase;

class EloquentIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new Capsule;
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        Capsule::schema()->create('orders', function ($table) {
            $table->increments('id');
            $table->json('shipping_address')->nullable();
            $table->json('line_items')->nullable();
            $table->timestamps();
        });
    }

    /** @test */
    public function it_can_save_and_retrieve_embedded_model()
    {
        $order = new TestOrder();
        $order->shipping_address = new TestShippingAddress([
            'street' => '123 Main St',
            'city' => 'Springfield',
            'zip' => '12345',
        ]);
        $order->save();

        $retrieved = TestOrder::find($order->id);

        $this->assertInstanceOf(TestShippingAddress::class, $retrieved->shipping_address);
        $this->assertEquals('123 Main St', $retrieved->shipping_address->street);
        $this->assertEquals('Springfield', $retrieved->shipping_address->city);
        $this->assertEquals('12345', $retrieved->shipping_address->zip);
    }

    /** @test */
    public function it_can_save_and_retrieve_embedded_collection()
    {
        $order = new TestOrder();
        $order->line_items = new TestOrderLineItemCollection([
            ['sku' => 'ABC', 'quantity' => 5, 'price' => 10.00],
            ['sku' => 'DEF', 'quantity' => 3, 'price' => 15.00],
        ]);
        $order->save();

        $retrieved = TestOrder::find($order->id);

        $this->assertInstanceOf(TestOrderLineItemCollection::class, $retrieved->line_items);
        $this->assertCount(2, $retrieved->line_items);
        $this->assertEquals('ABC', $retrieved->line_items[0]->sku);
        $this->assertEquals(5, $retrieved->line_items[0]->quantity);
    }

    /** @test */
    public function it_can_modify_embedded_model_and_save()
    {
        $order = new TestOrder();
        $order->shipping_address = new TestShippingAddress([
            'street' => '123 Main St',
            'city' => 'Springfield',
            'zip' => '12345',
        ]);
        $order->save();

        $order->shipping_address->street = '456 Oak Ave';
        $order->save();

        $retrieved = TestOrder::find($order->id);

        $this->assertEquals('456 Oak Ave', $retrieved->shipping_address->street);
    }

    /** @test */
    public function it_can_modify_collection_items_and_save()
    {
        $order = new TestOrder();
        $order->line_items = new TestOrderLineItemCollection([
            ['sku' => 'ABC', 'quantity' => 5, 'price' => 10.00],
        ]);
        $order->save();

        $order->line_items[0]->quantity = 10;
        $order->line_items->push(['sku' => 'DEF', 'quantity' => 3, 'price' => 15.00]);
        $order->save();

        $retrieved = TestOrder::find($order->id);

        $this->assertCount(2, $retrieved->line_items);
        $this->assertEquals(10, $retrieved->line_items[0]->quantity);
        $this->assertEquals('DEF', $retrieved->line_items[1]->sku);
    }

    /** @test */
    public function it_handles_null_embedded_model()
    {
        $order = new TestOrder();
        $order->save();

        $retrieved = TestOrder::find($order->id);

        $this->assertNull($retrieved->shipping_address);
    }

    /** @test */
    public function it_handles_null_embedded_collection()
    {
        $order = new TestOrder();
        $order->save();

        $retrieved = TestOrder::find($order->id);

        $this->assertNull($retrieved->line_items);
    }

    /** @test */
    public function it_supports_nested_embedded_models()
    {
        $order = new TestOrder();
        $order->shipping_address = new TestShippingAddress([
            'street' => '123 Main St',
            'city' => 'Springfield',
            'zip' => '12345',
            'coordinates' => ['lat' => 40.7128, 'lng' => -74.0060],
        ]);
        $order->save();

        $retrieved = TestOrder::find($order->id);

        $this->assertInstanceOf(TestGeoCoordinates::class, $retrieved->shipping_address->coordinates);
        $this->assertEquals(40.7128, $retrieved->shipping_address->coordinates->lat);
        $this->assertEquals(-74.0060, $retrieved->shipping_address->coordinates->lng);
    }

    /** @test */
    public function it_serializes_embedded_models_to_json_correctly()
    {
        $order = new TestOrder();
        $order->shipping_address = new TestShippingAddress([
            'street' => '123 Main St',
            'city' => 'Springfield',
            'zip' => '12345',
        ]);
        $order->save();

        $json = $order->toJson();
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded['shipping_address']);
        $this->assertEquals('123 Main St', $decoded['shipping_address']['street']);
    }

    /** @test */
    public function it_can_create_model_with_embedded_data_via_create()
    {
        $order = TestOrder::create([
            'shipping_address' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
                'zip' => '12345',
            ],
        ]);

        $retrieved = TestOrder::find($order->id);

        $this->assertInstanceOf(TestShippingAddress::class, $retrieved->shipping_address);
        $this->assertEquals('123 Main St', $retrieved->shipping_address->street);
    }

    /** @test */
    public function embedded_model_casts_work_correctly()
    {
        $order = new TestOrder();
        $order->line_items = new TestOrderLineItemCollection([
            ['sku' => 'ABC', 'quantity' => '5', 'price' => '10.50'],
        ]);
        $order->save();

        $retrieved = TestOrder::find($order->id);

        // Integer cast should work
        $this->assertSame(5, $retrieved->line_items[0]->quantity);
        // Float cast should work
        $this->assertSame(10.5, $retrieved->line_items[0]->price);
    }

    /** @test */
    public function embedded_model_casts_as_function_work_correctly()
    {
        $order = new TestOrderWithCastFunction();
        $order->line_items = new TestOrderLineItemCollection([
            ['sku' => 'ABC', 'quantity' => '5', 'price' => '10.50'],
        ]);
        $order->save();

        $retrieved = TestOrderWithCastFunction::find($order->id);

        // Integer cast should work
        $this->assertSame(5, $retrieved->line_items[0]->quantity);
        // Float cast should work
        $this->assertSame(10.5, $retrieved->line_items[0]->price);
    }
}

// Test Eloquent Model

class TestOrder extends Model
{
    protected $table = 'orders';
    protected $guarded = [];

    protected $casts = [
        'shipping_address' => AsEmbeddedModel::class . ':' . TestShippingAddress::class,
        'line_items' => AsEmbeddedCollection::class . ':' . TestOrderLineItemCollection::class,
    ];
}

class TestOrderWithCastFunction extends Model
{
    protected $table = 'orders';
    protected $guarded = [];


    protected function casts()
    {
        return [
            'shipping_address' => AsEmbeddedModel::of(TestShippingAddress::class),
            'line_items' => AsEmbeddedCollection::of(TestOrderLineItemCollection::class),
        ];
    }
}

// Test Embedded Models

class TestShippingAddress extends EmbeddedModel
{
    protected function casts() {
        return ['coordinates' => TestGeoCoordinates::class];
    }
}

class TestGeoCoordinates extends EmbeddedModel
{
    //
}

class TestOrderLineItem extends EmbeddedModel
{
    protected array $casts = [
        'quantity' => 'integer',
        'price' => 'float',
    ];
}

class TestOrderLineItemCollection extends EmbeddedCollection
{
    protected function getDefaultModelClass(): string
    {
        return TestOrderLineItem::class;
    }
}
