<?php

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Validation\ValidationException;
use Juanparati\EmbedModels\EmbedModel;

it('can create embedded model with attributes', function () {
    $address = new TestAddress(['street' => '123 Main St', 'city' => 'Springfield']);

    expect($address->street)->toBe('123 Main St');
    expect($address->city)->toBe('Springfield');
});

it('supports array access', function () {
    $address = new TestAddress(['street' => '123 Main St']);

    expect($address['street'])->toBe('123 Main St');

    $address['city'] = 'Springfield';
    expect($address['city'])->toBe('Springfield');
});

it('respects fillable attributes', function () {
    $address = new TestAddressWithFillable(['street' => '123 Main St', 'internal_id' => 'secret']);

    expect($address->street)->toBe('123 Main St');
    expect($address->internal_id)->toBeNull();
});

it('respects guarded attributes', function () {
    $address = new TestAddressWithGuarded(['street' => '123 Main St', 'internal_id' => 'secret']);

    expect($address->street)->toBe('123 Main St');
    expect($address->internal_id)->toBeNull();
});

it('validates attributes on set', function () {
    $address = new TestAddressWithValidation;
    $address->zip = 'invalid';
})->throws(ValidationException::class);

it('allows valid attributes', function () {
    $address = new TestAddressWithValidation;
    $address->zip = '12345';

    expect($address->zip)->toBe('12345');
});

it('validates on construction', function () {
    new TestAddressWithValidation(['zip' => 'invalid']);
})->throws(ValidationException::class);

it('casts integer attributes', function () {
    $model = new TestModelWithCasts(['age' => '25']);

    expect($model->age)->toBe(25);
});

it('casts boolean attributes', function () {
    $model = new TestModelWithCasts(['active' => 1]);

    expect($model->active)->toBe(true);
});

it('casts array attributes', function () {
    $model = new TestModelWithCasts(['options' => '{"key":"value"}']);

    expect($model->options)->toBeArray();
    expect($model->options)->toBe(['key' => 'value']);
});

it('casts datetime attributes', function () {
    $model = new TestModelWithCasts(['created_at' => '2024-01-01 12:00:00']);

    expect($model->created_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('supports nested embedded models', function () {
    $address = new TestAddressWithNested([
        'street' => '123 Main St',
        'coordinates' => ['lat' => 40.7128, 'lng' => -74.0060],
    ]);

    expect($address->coordinates)->toBeInstanceOf(TestCoordinates::class);
    expect($address->coordinates->lat)->toBe(40.7128);
    expect($address->coordinates->lng)->toBe(-74.0060);
});

it('converts to array', function () {
    $address = new TestAddress(['street' => '123 Main St', 'city' => 'Springfield']);

    $array = $address->toArray();

    expect($array)->toBe([
        'street' => '123 Main St',
        'city' => 'Springfield',
    ]);
});

it('converts to json', function () {
    $address = new TestAddress(['street' => '123 Main St', 'city' => 'Springfield']);

    $json = $address->toJson();

    expect($json)->toBeJson();
    expect($json)->toBe('{"street":"123 Main St","city":"Springfield"}');
});

it('converts nested models to array', function () {
    $address = new TestAddressWithNested([
        'street' => '123 Main St',
        'coordinates' => ['lat' => 40.7128, 'lng' => -74.0060],
    ]);

    $array = $address->toArray();

    expect($array)->toBe([
        'street' => '123 Main St',
        'coordinates' => [
            'lat' => 40.7128,
            'lng' => -74.0060,
        ],
    ]);
});

it('supports accessors', function () {
    $model = new TestModelWithAccessor(['first_name' => 'John', 'last_name' => 'Doe']);

    expect($model->full_name)->toBe('John Doe');
});

it('supports mutators', function () {
    $model = new TestModelWithMutator;
    $model->email = 'JOHN@EXAMPLE.COM';

    expect($model->email)->toBe('john@example.com');
});

it('handles null values', function () {
    $address = new TestAddress(['street' => null]);

    expect($address->street)->toBeNull();
});

it('supports isset check', function () {
    $address = new TestAddress(['street' => '123 Main St']);

    expect(isset($address->street))->toBeTrue();
    expect(isset($address->city))->toBeFalse();
});

it('supports unset', function () {
    $address = new TestAddress(['street' => '123 Main St', 'city' => 'Springfield']);

    unset($address->city);

    expect($address->city)->toBeNull();
    expect($address->street)->toBe('123 Main St');
});

it('uses casts from function', function () {
    $model = new TestWithCastFunction(['age' => '25']);

    expect($model->age)->toBe(25);
});

it('handles get mutator with attribute class', function () {
    $model = new TestModelWithMutatorSetterMethod(['name' => 'john doe']);

    expect($model->name)->toBe('JOHN DOE');
});

it('handles set mutator with attribute class', function () {
    $model = new TestModelWithMutatorSetterMethod;
    $model->name = '  john   doe  ';

    expect($model->name)->toBe('JOHN DOE');
});

it('handles enum mutator', function () {
    $model = new TestModelWithEnumCast;
    $model->enum = TestEnum::Option2;

    expect($model->enum)->toBe(TestEnum::Option2);
    expect($model->toArray())->toBe(['enum' => 'option2']);
});

it('generates virtual attributes', function () {
    $model = new TestModelWithVirtualAttribute;
    $model->foo = 'bar';

    expect($model->generated_attr)->toBe('Generated Attribute');
    expect($model->virtual_attr)->toBe('Virtual Attribute');
    expect($model->foo)->toBe('bar');
});

// Test classes

class TestAddress extends EmbedModel
{
    //
}

class TestAddressWithFillable extends EmbedModel
{
    protected $fillable = ['street'];
}

class TestAddressWithGuarded extends EmbedModel
{
    protected $guarded = ['internal_id'];
}

class TestAddressWithValidation extends EmbedModel
{
    protected function rules()
    {
        return [
            'zip' => 'required|regex:/^\d{5}$/',
        ];
    }
}

class TestModelWithCasts extends EmbedModel
{
    protected function casts()
    {
        return [
            'age' => 'integer',
            'active' => 'boolean',
            'options' => 'array',
            'created_at' => 'datetime',
        ];
    }
}

class TestCoordinates extends EmbedModel
{
    //
}

class TestAddressWithNested extends EmbedModel
{
    protected $casts = [
        'coordinates' => TestCoordinates::class,
    ];
}

class TestWithCastFunction extends EmbedModel
{
    protected function casts(): array
    {
        return [
            'age' => 'integer',
        ];
    }
}

class TestModelWithAccessor extends EmbedModel
{
    public function getFullNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }
}

class TestModelWithMutator extends EmbedModel
{
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }
}

class TestModelWithMutatorSetterMethod extends EmbedModel
{
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => strtoupper($value),
            set: fn ($value) => \Str::squish($value),
        );
    }
}

enum TestEnum: string
{
    case Option1 = 'option1';
    case Option2 = 'option2';
}

class TestModelWithEnumCast extends EmbedModel
{
    protected function casts(): array
    {
        return [
            'enum' => TestEnum::class,
        ];
    }
}

class TestModelWithVirtualAttribute extends EmbedModel
{
    protected $attributes = [
        'virtual_attr' => 'Virtual Attribute',
    ];

    protected $appends = ['generated_attr'];

    protected function generatedAttr(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Generated Attribute',
        );
    }
}
