<?php

namespace Juanparati\EmbedModels\Contracts;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;


interface EmbedCollectionInterface extends \Countable, \IteratorAggregate, Arrayable, \ArrayAccess, Jsonable, \JsonSerializable
{
    public function getCollection(): Collection;

    public function all(): array;

    public function first(?callable $callback = null, $default = null): mixed;

    public function last(?callable $callback = null, $default = null): mixed;

    public function push($value): static;

    public function put($key, $value): static;

    public function get($key, $default = null): mixed;

    public function has($key): bool;

    public function forget($key): static;

    public function isEmpty(): bool;

    public function isNotEmpty(): bool;

    public function filter(?callable $callback = null): static;

    public function map(callable $callback): Collection;
}
