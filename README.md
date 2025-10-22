# Eloquent Embed Models

A Laravel package that allows to embed sub-models inside JSON fields in Eloquent models. These embedded models behave like full Eloquent models with attributes, guards and casts.


## Features

- **Model Behavior**: Embedded models support attributes, accessors, mutators, casts, fillable/guarded
- **Validation**: Validate embedded model attributes on write with Laravel's validation rules
- **Nested Support**: Embed models within embedded models
- **Collections**: Handle arrays of embedded models with collection-like interfaces
- **Array Access**: Access embedded model attributes like arrays
- **Type Casting**: Support for all Eloquent casting types (dates, booleans, integers, etc.)
- **No Persistence Methods**: Embedded models don't have save/update/delete - they're saved via the parent model
- **JSON raw**: Save embedded models as JSON raw strings (not objects serialization)
- **Transparent hydration**: Inject your nested embed-model directly from a post request or array.


## Installation

```bash
composer require juanparati/embed-models
```


## Basic usage

### 1. Define your embedded model

```php
use Juanparati\EmbedModels\EmbedModel;

class Address extends EmbedModel
{
    protected $fillable = ['street', 'city', 'zip', 'country'];

    protected function casts() {
        return [
            'verified' => 'boolean',
        ];
    }   

    protected function rules()
    {
        return [
            'zip' => 'required|regex:/^\d{5}$/',
            'street' => 'required|string',
        ];
    }
}
```

Note: All embed-models attributes are fillable by default in opposition to normal Eloquent models.

You can define default values for attributes, so the embed will have a basic structure:

```php
use Juanparati\EmbedModels\EmbedModel;

class Address extends EmbedModel
{    
    protected $attributes = [
        'street' => '',
        'city' => 'Aarhus',
        'zip' => '8200',
        'country' => 'Denmark',
    ];   
}
```


### 2. Use it in your eloquent model

```php
use Illuminate\Database\Eloquent\Model;
use Juanparati\EmbedModels\Casts\AsEmbeddedModel;

class Order extends Model
{
    protected function casts() {
        return [
            'shipping_address' => AsEmbeddedModel::of(Address::class),
        ];
    }  
}
```


### 3. Work with embedded models

```php
// Create an order with an embedded address
$order = new Order();
$order->shipping_address = new Address([
    'street' => '123 Main St',
    'city' => 'Springfield',
    'zip' => '12345',
]);
$order->save();

// Modify the embedded model
$order->shipping_address->street = '456 Oak Ave';
$order->save();

// Access via array syntax
$order->shipping_address['city'] = 'New Springfield';

// Access attributes
echo $order->shipping_address->street; // "456 Oak Ave"
```


## Collections of embedded models

### 1. Define your collection

```php
use Juanparati\EmbedModels\EmbedModel;
use Juanparati\EmbedModels\EmbedCollection;

class LineItem extends EmbedModel
{
    protected function casts() 
    {
        return [
            'quantity' => 'integer',
            'price' => 'float',
        ];
    }
    
    protected function rules()
    {
        return [
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ];
    }
}

class LineItemCollection extends EmbedCollection
{
    protected function getDefaultModelClass()
    {
        return LineItem::class;
    }
}
```

### 2. Use in your model

```php
use Juanparati\EmbedModels\Casts\AsEmbedCollection;

class Order extends Model
{

    protected function casts() {
        return [
            'line_items' => AsEmbedCollection::of(LineItemCollection::class),
        ];  
    }   
}
```

### 3. Work with collections

```php
$order = new Order();
$order->line_items = new LineItemCollection([
    ['sku' => 'ABC', 'quantity' => 5, 'price' => 10.00],
    ['sku' => 'DEF', 'quantity' => 3, 'price' => 15.00],
]);

// Add items
$order->line_items[] = new LineItem(['sku' => 'GHI', 'quantity' => 2, 'price' => 20.00]);
$order->line_items->push(['sku' => 'JKL', 'quantity' => 1, 'price' => 25.00]);

// Access items
echo $order->line_items[3]->sku; // "JKL"

// Use collection methods
$total = $order->line_items->sum(fn($item) => $item->quantity * $item->price);

// Filter
$expensive = $order->line_items->filter(fn($item) => $item->price > 15);

$order->save();
```

You can also transparently append arrays to embed collections, and they are automatically getting converted to embedded models:

```php
$order = new Order();
$order->line_items = [
    ['sku' => 'ABC', 'quantity' => 5, 'price' => 10.00],
    ['sku' => 'DEF', 'quantity' => 3, 'price' => 15.00],
];

// Add items
$order->line_items[] = ['sku' => 'GHI', 'quantity' => 2, 'price' => 20.00];
$order->line_items[] = ['sku' => 'JKL', 'quantity' => 1, 'price' => 25.00];

// Access items
echo $order->line_items[3]->sku; // "JKL"
```


## Advanced features

### Nested embedded models

```php
class Coordinates extends EmbeddedModel
{
    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
    ];
}

class Address extends EmbeddedModel
{
    protected $casts = [
        'coordinates' => AsEmbedModel::of(Coordinates::class),
    ];
}

// Usage
$order->shipping_address = new Address([
    'street' => '123 Main St',
    'coordinates' => ['lat' => 40.7128, 'lng' => -74.0060],
]);

echo $order->shipping_address->coordinates->lat; // 40.7128
```

### Accessors and mutators

```php
class Address extends EmbeddedModel
{
    protected function fullAddress(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return Attribute::make(
            get: fn() => "{$this->street}, {$this->city}, {$this->zip}"
        );
    }

    protected function zipAttribute(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return Attribute::make(
            set: fn($value) => str_pad($value, 5, '0', STR_PAD_LEFT)
        );     
    }
}
```

You can also use legacy accessors and mutators:

```php
class Address extends EmbeddedModel
{
    public function getFullAddressAttribute()
    {
        return "{$this->street}, {$this->city}, {$this->zip}";
    }

    public function setZipAttribute($value)
    {
        return str_pad($value, 5, '0', STR_PAD_LEFT);
    }
}
```

### Mass assignment protection

Embed models are fillable by default in opposition of normal Eloquent models, but you can also still use guarded attributes:

```php
class Address extends EmbeddedModel
{
    protected $fillable = ['street', 'city', 'zip'];
    // or
    protected $guarded = ['internal_id'];
}

// This will ignore 'internal_id'
$address = new Address([
    'street' => '123 Main St',
    'internal_id' => 'secret', // ignored
]);
```

### Validation

In case that validation rules are defined, they will be automatically applied to embedded models.

Validation runs automatically when you set attributes:

```php
class Address extends EmbeddedModel
{
    protected function rules()
    {
        return [
            'zip' => 'required|regex:/^\d{5}$/',
            'email' => 'required|email',
        ];
    }
}

// This will throw ValidationException
$address = new Address();
$address->zip = 'invalid'; // Throws ValidationException
```

### Type Casting

All Eloquent casting types are supported:

```php
class Product extends EmbeddedModel
{
    protected $casts = [
        'price' => 'float',
        'quantity' => 'integer',
        'active' => 'boolean',
        'options' => 'array',
        'released_at' => 'datetime',
        'metadata' => 'json',
        ...
    ];
}
```


## Behavior Notes

1. **No Auto-Dirty Tracking**: Modifying embedded models doesn't automatically mark the parent as dirty. You must explicitly call `$parent->save()`.

2. **Null Initialization**: If a JSON field is null in the database, accessing it returns `null`, not an empty embedded model.

3. **Validation Timing**: Validation runs when attributes are set, not when the parent model saves.

4. **No Persistence Methods**: Embedded models don't have `save()`, `update()`, or `delete()` methods. They're saved only through the parent model.


