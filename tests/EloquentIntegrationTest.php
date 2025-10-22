<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Juanparati\EmbedModels\Casts\AsEmbedCollection;
use Juanparati\EmbedModels\Casts\AsEmbedModel;
use Juanparati\EmbedModels\EmbedCollection;
use Juanparati\EmbedModels\EmbedModel;

beforeEach(function () {
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
});

it('can save and retrieve embedded model', function () {
    $order = new TestOrder;
    $order->shipping_address = new TestShippingAddress([
        'street' => '123 Main St',
        'city' => 'Springfield',
        'zip' => '12345',
    ]);
    $order->save();

    $retrieved = TestOrder::find($order->id);

    expect($retrieved->shipping_address)->toBeInstanceOf(TestShippingAddress::class);
    expect($retrieved->shipping_address->street)->toBe('123 Main St');
    expect($retrieved->shipping_address->city)->toBe('Springfield');
    expect($retrieved->shipping_address->zip)->toBe('12345');
});

it('can save and retrieve embedded collection', function () {
    $order = new TestOrder;
    $order->line_items = new TestOrderLineItemCollection([
        ['sku' => 'ABC', 'quantity' => 5, 'price' => 10.00],
        ['sku' => 'DEF', 'quantity' => 3, 'price' => 15.00],
    ]);
    $order->save();

    $retrieved = TestOrder::find($order->id);

    expect($retrieved->line_items)->toBeInstanceOf(TestOrderLineItemCollection::class);
    expect($retrieved->line_items)->toHaveCount(2);
    expect($retrieved->line_items[0]->sku)->toBe('ABC');
    expect($retrieved->line_items[0]->quantity)->toBe(5);
});

it('can modify embedded model and save', function () {
    $order = new TestOrder;
    $order->shipping_address = new TestShippingAddress([
        'street' => '123 Main St',
        'city' => 'Springfield',
        'zip' => '12345',
    ]);
    $order->save();

    $order->shipping_address->street = '456 Oak Ave';
    $order->save();

    $retrieved = TestOrder::find($order->id);

    expect($retrieved->shipping_address->street)->toBe('456 Oak Ave');
});

it('can modify collection items and save', function () {
    $order = new TestOrder;
    $order->line_items = new TestOrderLineItemCollection([
        ['sku' => 'ABC', 'quantity' => 5, 'price' => 10.00],
    ]);
    $order->save();

    $order->line_items[0]->quantity = 10;
    $order->line_items->push(['sku' => 'DEF', 'quantity' => 3, 'price' => 15.00]);
    $order->save();

    $retrieved = TestOrder::find($order->id);

    expect($retrieved->line_items)->toHaveCount(2);
    expect($retrieved->line_items[0]->quantity)->toBe(10);
    expect($retrieved->line_items[1]->sku)->toBe('DEF');
});

it('handles null embedded model', function () {
    $order = new TestOrder;
    $order->save();

    $retrieved = TestOrder::find($order->id);

    expect($retrieved->shipping_address)->toBeNull();
});

it('handles null embedded collection', function () {
    $order = new TestOrder;
    $order->save();

    $retrieved = TestOrder::find($order->id);

    expect($retrieved->line_items)->toBeNull();
});

it('supports nested embedded models', function () {
    $order = new TestOrder;
    $order->shipping_address = new TestShippingAddress([
        'street' => '123 Main St',
        'city' => 'Springfield',
        'zip' => '12345',
        'coordinates' => ['lat' => 40.7128, 'lng' => -74.0060],
    ]);
    $order->save();

    $retrieved = TestOrder::find($order->id);

    expect($retrieved->shipping_address->coordinates)->toBeInstanceOf(TestGeoCoordinates::class);
    expect($retrieved->shipping_address->coordinates->lat)->toBe(40.7128);
    expect($retrieved->shipping_address->coordinates->lng)->toBe(-74.0060);
});

it('serializes embedded models to json correctly', function () {
    $order = new TestOrder;
    $order->shipping_address = new TestShippingAddress([
        'street' => '123 Main St',
        'city' => 'Springfield',
        'zip' => '12345',
    ]);
    $order->save();

    $json = $order->toJson();
    $decoded = json_decode($json, true);

    expect($decoded['shipping_address'])->toBeArray();
    expect($decoded['shipping_address']['street'])->toBe('123 Main St');
});

it('can create model with embedded data via create', function () {
    $order = TestOrder::create([
        'shipping_address' => [
            'street' => '123 Main St',
            'city' => 'Springfield',
            'zip' => '12345',
        ],
    ]);

    $retrieved = TestOrder::find($order->id);

    expect($retrieved->shipping_address)->toBeInstanceOf(TestShippingAddress::class);
    expect($retrieved->shipping_address->street)->toBe('123 Main St');
});

it('has embedded model casts work correctly', function () {
    $order = new TestOrder;
    $order->line_items = new TestOrderLineItemCollection([
        ['sku' => 'ABC', 'quantity' => '5', 'price' => '10.50'],
    ]);

    $order->save();

    $retrieved = TestOrder::find($order->id);

    // Integer cast should work
    expect($retrieved->line_items[0]->quantity)->toBe(5);
    // Float cast should work
    expect($retrieved->line_items[0]->price)->toBe(10.5);
});

it('has embedded model casts work correctly transparently', function () {
    $order = new TestOrder;
    $order->line_items = [
        ['sku' => 'ABC', 'quantity' => '5', 'price' => '10.50'],
    ];

    $order->line_items[] = ['sku' => 'DEF', 'quantity' => '3', 'price' => '15.20'];

    $order->save();

    $retrieved = TestOrder::find($order->id);

    // Integer cast should work
    expect($retrieved->line_items[0]->quantity)->toBe(5);
    // Float cast should work
    expect($retrieved->line_items[0]->price)->toBe(10.5);
    expect($retrieved->line_items[1]->price)->toBe(15.2);
    // Check append line
    expect($retrieved->line_items[1]->sku)->toBe('DEF');
});

it('has embedded model casts as function work correctly', function () {
    $order = new TestOrderWithCastFunction;
    $order->line_items = new TestOrderLineItemCollection([
        ['sku' => 'ABC', 'quantity' => '5', 'price' => '10.50'],
    ]);
    $order->save();

    $retrieved = TestOrderWithCastFunction::find($order->id);

    // Integer cast should work
    expect($retrieved->line_items[0]->quantity)->toBe(5);
    // Float cast should work
    expect($retrieved->line_items[0]->price)->toBe(10.5);
});

// Test Eloquent Model

class TestOrder extends Model
{
    protected $table = 'orders';

    protected $guarded = [];

    protected $casts = [
        'shipping_address' => AsEmbedModel::class.':'.TestShippingAddress::class,
        'line_items' => AsEmbedCollection::class.':'.TestOrderLineItemCollection::class,
    ];
}

class TestOrderWithCastFunction extends Model
{
    protected $table = 'orders';

    protected $guarded = [];

    protected function casts()
    {
        return [
            'shipping_address' => AsEmbedModel::of(TestShippingAddress::class),
            'line_items' => AsEmbedCollection::of(TestOrderLineItemCollection::class),
        ];
    }
}

// Test Embedded Models
class TestShippingAddress extends EmbedModel
{
    protected function casts()
    {
        return ['coordinates' => AsEmbedModel::of(TestGeoCoordinates::class)];
    }
}

class TestGeoCoordinates extends EmbedModel
{
    //
}

class TestOrderLineItem extends EmbedModel
{
    protected $casts = [
        'quantity' => 'integer',
        'price' => 'float',
    ];
}

class TestOrderLineItemCollection extends EmbedCollection
{
    protected function getDefaultModelClass(): string
    {
        return TestOrderLineItem::class;
    }
}
