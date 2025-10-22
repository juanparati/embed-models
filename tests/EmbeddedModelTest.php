<?php

namespace Juanparati\EmbedModels\Tests;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Validation\ValidationException;
use Juanparati\EmbedModels\EmbedModel;
use Orchestra\Testbench\TestCase;

class EmbeddedModelTest extends TestCase
{
    /** @test */
    public function it_can_create_embedded_model_with_attributes()
    {
        $address = new TestAddress(['street' => '123 Main St', 'city' => 'Springfield']);

        $this->assertEquals('123 Main St', $address->street);
        $this->assertEquals('Springfield', $address->city);
    }

    /** @test */
    public function it_supports_array_access()
    {
        $address = new TestAddress(['street' => '123 Main St']);

        $this->assertEquals('123 Main St', $address['street']);

        $address['city'] = 'Springfield';
        $this->assertEquals('Springfield', $address['city']);
    }

    /** @test */
    public function it_respects_fillable_attributes()
    {
        $address = new TestAddressWithFillable(['street' => '123 Main St', 'internal_id' => 'secret']);

        $this->assertEquals('123 Main St', $address->street);
        $this->assertNull($address->internal_id);
    }

    /** @test */
    public function it_respects_guarded_attributes()
    {
        $address = new TestAddressWithGuarded(['street' => '123 Main St', 'internal_id' => 'secret']);

        $this->assertEquals('123 Main St', $address->street);
        $this->assertNull($address->internal_id);
    }

    /** @test */
    public function it_validates_attributes_on_set()
    {
        $this->expectException(ValidationException::class);

        $address = new TestAddressWithValidation;
        $address->zip = 'invalid';
    }

    /** @test */
    public function it_allows_valid_attributes()
    {
        $address = new TestAddressWithValidation;
        $address->zip = '12345';

        $this->assertEquals('12345', $address->zip);
    }

    /** @test */
    public function it_validates_on_construction()
    {
        $this->expectException(ValidationException::class);

        new TestAddressWithValidation(['zip' => 'invalid']);
    }

    /** @test */
    public function it_casts_integer_attributes()
    {
        $model = new TestModelWithCasts(['age' => '25']);

        $this->assertSame(25, $model->age);
    }

    /** @test */
    public function it_casts_boolean_attributes()
    {
        $model = new TestModelWithCasts(['active' => 1]);

        $this->assertSame(true, $model->active);
    }

    /** @test */
    public function it_casts_array_attributes()
    {
        $model = new TestModelWithCasts(['options' => '{"key":"value"}']);

        $this->assertIsArray($model->options);
        $this->assertEquals(['key' => 'value'], $model->options);
    }

    /** @test */
    public function it_casts_datetime_attributes()
    {
        $model = new TestModelWithCasts(['created_at' => '2024-01-01 12:00:00']);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $model->created_at);
    }

    /** @test */
    public function it_supports_nested_embedded_models()
    {
        $address = new TestAddressWithNested([
            'street' => '123 Main St',
            'coordinates' => ['lat' => 40.7128, 'lng' => -74.0060],
        ]);

        $this->assertInstanceOf(TestCoordinates::class, $address->coordinates);
        $this->assertEquals(40.7128, $address->coordinates->lat);
        $this->assertEquals(-74.0060, $address->coordinates->lng);
    }

    /** @test */
    public function it_converts_to_array()
    {
        $address = new TestAddress(['street' => '123 Main St', 'city' => 'Springfield']);

        $array = $address->toArray();

        $this->assertEquals([
            'street' => '123 Main St',
            'city' => 'Springfield',
        ], $array);
    }

    /** @test */
    public function it_converts_to_json()
    {
        $address = new TestAddress(['street' => '123 Main St', 'city' => 'Springfield']);

        $json = $address->toJson();

        $this->assertJson($json);
        $this->assertEquals('{"street":"123 Main St","city":"Springfield"}', $json);
    }

    /** @test */
    public function it_converts_nested_models_to_array()
    {
        $address = new TestAddressWithNested([
            'street' => '123 Main St',
            'coordinates' => ['lat' => 40.7128, 'lng' => -74.0060],
        ]);

        $array = $address->toArray();

        $this->assertEquals([
            'street' => '123 Main St',
            'coordinates' => [
                'lat' => 40.7128,
                'lng' => -74.0060,
            ],
        ], $array);
    }

    /** @test */
    public function it_supports_accessors()
    {
        $model = new TestModelWithAccessor(['first_name' => 'John', 'last_name' => 'Doe']);

        $this->assertEquals('John Doe', $model->full_name);
    }

    /** @test */
    public function it_supports_mutators()
    {
        $model = new TestModelWithMutator;
        $model->email = 'JOHN@EXAMPLE.COM';

        $this->assertEquals('john@example.com', $model->email);
    }

    /** @test */
    public function it_handles_null_values()
    {
        $address = new TestAddress(['street' => null]);

        $this->assertNull($address->street);
    }

    /** @test */
    public function it_supports_isset_check()
    {
        $address = new TestAddress(['street' => '123 Main St']);

        $this->assertTrue(isset($address->street));
        $this->assertFalse(isset($address->city));
    }

    /** @test */
    public function it_supports_unset()
    {
        $address = new TestAddress(['street' => '123 Main St', 'city' => 'Springfield']);

        unset($address->city);

        $this->assertNull($address->city);
        $this->assertEquals('123 Main St', $address->street);
    }

    /** @test */
    public function it_uses_casts_from_function()
    {
        $model = new TestWithCastFunction(['age' => '25']);

        $this->assertSame(25, $model->age);
    }

    /** @test */
    public function it_handles_get_mutator_with_attribute_class()
    {
        $model = new TestModelWithMutatorSetterMethod(['name' => 'john doe']);

        $this->assertEquals('JOHN DOE', $model->name);
    }

    /** @test */
    public function it_handles_set_mutator_with_attribute_class()
    {
        $model = new TestModelWithMutatorSetterMethod;
        $model->name = '  john   doe  ';

        $this->assertEquals('JOHN DOE', $model->name);
    }

    /** @test */
    public function it_handles_enum_mutator()
    {
        $model = new TestModelWithEnumCast;
        $model->enum = TestEnum::Option2;

        $this->assertEquals(TestEnum::Option2, $model->enum);
        $this->assertEquals(['enum' => 'option2'], $model->toArray());
    }

    /** @test */
    public function it_generates_virtual_attributes()
    {
        $model = new TestModelWithVirtualAttribute;
        $model->foo = 'bar';


        $this->assertEquals('Generated Attribute', $model->generated_attr);
        $this->assertEquals('Virtual Attribute', $model->virtual_attr);
        $this->assertEquals('bar', $model->foo);
    }

}

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
