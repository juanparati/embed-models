<?php

/**
 * Basic Usage Example for Eloquent Embed Models
 *
 * This example demonstrates how to use the package in a real Laravel application.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use EloquentEmbedModels\EmbeddedModel;
use EloquentEmbedModels\EmbeddedCollection;
use EloquentEmbedModels\Casts\AsEmbeddedModel;
use EloquentEmbedModels\Casts\AsEmbeddedCollection;

// ============================================================================
// Step 1: Define your Embedded Models
// ============================================================================

class Address extends EmbeddedModel
{
    protected array $fillable = ['street', 'city', 'state', 'zip', 'country'];

    protected $casts = [
        'coordinates' => Coordinates::class,
    ];

    protected function rules()
    {
        return [
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'zip' => 'required|regex:/^\d{5}(-\d{4})?$/',
        ];
    }

    // Custom accessor
    public function getFullAddressAttribute()
    {
        return "{$this->street}, {$this->city}, {$this->state} {$this->zip}";
    }
}

class Coordinates extends EmbeddedModel
{
    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
    ];

    protected function rules()
    {
        return [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ];
    }
}

class LineItem extends EmbeddedModel
{
    protected array $fillable = ['sku', 'name', 'quantity', 'price'];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'float',
        'discount_applied' => 'boolean',
    ];

    protected function rules()
    {
        return [
            'sku' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ];
    }

    // Calculate total for this line item
    public function getTotalAttribute()
    {
        return $this->quantity * $this->price;
    }
}

class LineItemCollection extends EmbeddedCollection
{
    protected function getDefaultModelClass(): string
    {
        return LineItem::class;
    }

    // Custom method to calculate order total
    public function calculateTotal()
    {
        return $this->sum(fn($item) => $item->total);
    }
}

// ============================================================================
// Step 2: Use in your Eloquent Model
// ============================================================================

class Order extends Model
{
    protected $fillable = ['customer_name', 'status'];

    protected $casts = [
        'shipping_address' => AsEmbeddedModel::class . ':' . Address::class,
        'billing_address' => AsEmbeddedModel::class . ':' . Address::class,
        'line_items' => AsEmbeddedCollection::class . ':' . LineItemCollection::class,
    ];
}

// ============================================================================
// Step 3: Usage Examples
// ============================================================================

// Example 1: Creating an order with embedded models
function createOrder()
{
    $order = new Order();
    $order->customer_name = 'John Doe';
    $order->status = 'pending';

    // Set shipping address
    $order->shipping_address = new Address([
        'street' => '123 Main St',
        'city' => 'Springfield',
        'state' => 'IL',
        'zip' => '62701',
        'country' => 'USA',
    ]);

    // Add coordinates to address
    $order->shipping_address->coordinates = new Coordinates([
        'lat' => 39.7817,
        'lng' => -89.6501,
    ]);

    // Add line items
    $order->line_items = new LineItemCollection([
        [
            'sku' => 'WIDGET-001',
            'name' => 'Blue Widget',
            'quantity' => 2,
            'price' => 19.99,
        ],
        [
            'sku' => 'GADGET-042',
            'name' => 'Red Gadget',
            'quantity' => 1,
            'price' => 49.99,
        ],
    ]);

    $order->save();

    return $order;
}

// Example 2: Modifying an existing order
function updateOrder($orderId)
{
    $order = Order::find($orderId);

    // Update address
    $order->shipping_address->street = '456 Oak Avenue';

    // Add a new line item
    $order->line_items->push([
        'sku' => 'TOOL-123',
        'name' => 'Green Tool',
        'quantity' => 3,
        'price' => 9.99,
    ]);

    // Modify existing line item
    $order->line_items[0]->quantity = 5;

    $order->save();

    return $order;
}

// Example 3: Using collection methods
function analyzeOrder($orderId)
{
    $order = Order::find($orderId);

    // Get total price
    $total = $order->line_items->calculateTotal();

    // Find expensive items
    $expensiveItems = $order->line_items->filter(fn($item) => $item->price > 20);

    // Get all SKUs
    $skus = $order->line_items->map(fn($item) => $item->sku);

    // Access nested data
    $latitude = $order->shipping_address->coordinates->lat;

    // Use accessor
    $fullAddress = $order->shipping_address->full_address;

    return [
        'total' => $total,
        'expensive_count' => $expensiveItems->count(),
        'skus' => $skus->all(),
        'latitude' => $latitude,
        'address' => $fullAddress,
    ];
}

// Example 4: Handling validation
function createOrderWithValidation()
{
    $order = new Order();

    try {
        // This will throw ValidationException because zip is invalid
        $order->shipping_address = new Address([
            'street' => '123 Main St',
            'city' => 'Springfield',
            'zip' => 'invalid',
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        // Handle validation error
        $errors = $e->errors();
        // ['zip' => ['The zip field format is invalid.']]
    }

    // This will succeed
    $order->shipping_address = new Address([
        'street' => '123 Main St',
        'city' => 'Springfield',
        'state' => 'IL',
        'zip' => '62701',
    ]);

    $order->save();
}

// Example 5: Array access
function useArrayAccess($orderId)
{
    $order = Order::find($orderId);

    // Access embedded model as array
    $city = $order->shipping_address['city'];

    // Set via array access
    $order->shipping_address['city'] = 'New Springfield';

    // Access collection items
    $firstItem = $order->line_items[0];
    $sku = $order->line_items[0]['sku'];

    $order->save();
}

// Example 6: JSON serialization
function serializeOrder($orderId)
{
    $order = Order::find($orderId);

    // Convert to array
    $array = $order->toArray();

    // Convert to JSON
    $json = $order->toJson();

    // The embedded models and collections are properly serialized
    return $json;
}
