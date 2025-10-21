<?php

namespace EloquentEmbedModels;

use Illuminate\Support\Collection;
use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use Traversable;

class EmbeddedCollection implements ArrayAccess, Arrayable, Jsonable, JsonSerializable, \Countable, \IteratorAggregate
{
    /**
     * The embedded model class name.
     *
     * @var string
     */
    protected string $modelClass;

    /**
     * The underlying collection instance.
     *
     * @var \Illuminate\Support\Collection
     */
    protected Collection $items;

    /**
     * Create a new embedded collection instance.
     *
     * @param  mixed  $items
     * @param string|null $modelClass
     */
    public function __construct(mixed $items = [], string $modelClass = null)
    {
        $this->modelClass = $modelClass ?? $this->getDefaultModelClass();
        $this->items = $this->makeCollection($items);
    }

    /**
     * Get the default model class for this collection.
     * Override this method in subclasses to specify the model class.
     *
     * @return string
     */
    protected function getDefaultModelClass(): string
    {
        // Try to infer from class name (e.g., LineItemCollection -> LineItem)
        $className = class_basename(static::class);

        if (str_ends_with($className, 'Collection')) {
            $modelName = substr($className, 0, -10); // Remove 'Collection'
            $namespace = (new \ReflectionClass($this))->getNamespaceName();
            $modelClass = $namespace . '\\' . $modelName;

            if (class_exists($modelClass)) {
                return $modelClass;
            }
        }

        return EmbeddedModel::class;
    }

    /**
     * Make a collection from the given items.
     *
     * @param  mixed  $items
     * @return \Illuminate\Support\Collection
     */
    protected function makeCollection($items): Collection
    {
        if ($items instanceof Collection) {
            return $items->map(fn($item) => $this->makeModel($item));
        }

        if (is_array($items)) {
            return collect($items)->map(fn($item) => $this->makeModel($item));
        }

        if (is_string($items)) {
            $decoded = json_decode($items, true);
            return collect($decoded ?? [])->map(fn($item) => $this->makeModel($item));
        }

        return collect();
    }

    /**
     * Make a model instance from the given data.
     *
     * @param  mixed  $data
     * @return \EloquentEmbedModels\EmbeddedModel
     */
    protected function makeModel($data): EmbeddedModel
    {
        if ($data instanceof $this->modelClass) {
            return $data;
        }

        if (is_array($data)) {
            return new $this->modelClass($data);
        }

        return $data;
    }

    /**
     * Get the underlying collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCollection(): Collection
    {
        return $this->items;
    }

    /**
     * Get all items in the collection.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->items->all();
    }

    /**
     * Get the first item from the collection.
     *
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public function first(callable $callback = null, $default = null): mixed
    {
        return $this->items->first($callback, $default);
    }

    /**
     * Get the last item from the collection.
     *
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public function last(callable $callback = null, $default = null): mixed
    {
        return $this->items->last($callback, $default);
    }

    /**
     * Push an item onto the end of the collection.
     *
     * @param  mixed  $value
     * @return $this
     */
    public function push($value): static
    {
        $this->items->push($this->makeModel($value));
        return $this;
    }

    /**
     * Put an item in the collection by key.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return $this
     */
    public function put($key, $value): static
    {
        $this->items->put($key, $this->makeModel($value));
        return $this;
    }

    /**
     * Get an item from the collection by key.
     *
     * @param  mixed  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null): mixed
    {
        return $this->items->get($key, $default);
    }

    /**
     * Determine if an item exists in the collection by key.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function has($key): bool
    {
        return $this->items->has($key);
    }

    /**
     * Remove an item from the collection by key.
     *
     * @param  mixed  $key
     * @return $this
     */
    public function forget($key): static
    {
        $this->items->forget($key);
        return $this;
    }

    /**
     * Get the number of items in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->items->count();
    }

    /**
     * Determine if the collection is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    /**
     * Determine if the collection is not empty.
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return $this->items->isNotEmpty();
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param  callable|null  $callback
     * @return static
     */
    public function filter(callable $callback = null): static
    {
        $filtered = $this->items->filter($callback);
        return new static($filtered, $this->modelClass);
    }

    /**
     * Map over each of the items.
     *
     * @param  callable  $callback
     * @return \Illuminate\Support\Collection
     */
    public function map(callable $callback): Collection
    {
        return $this->items->map($callback);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->items->map(function ($item) {
            return $item instanceof Arrayable ? $item->toArray() : $item;
        })->all();
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Determine if the given item exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return $this->items->offsetExists($offset);
    }

    /**
     * Get the item at the given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->items->offsetGet($offset);
    }

    /**
     * Set the item at the given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $model = $this->makeModel($value);

        if (is_null($offset)) {
            $this->items->push($model);
        } else {
            $this->items->offsetSet($offset, $model);
        }
    }

    /**
     * Unset the item at the given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        $this->items->offsetUnset($offset);
    }

    public function getIterator(): Traversable
    {
        return $this->items->getIterator();
    }

    /**
     * Dynamically access collection proxies.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->items->$method(...$parameters);
    }
}
