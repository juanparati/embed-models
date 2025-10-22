<?php

namespace Juanparati\EmbedModels\Contracts;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;


interface EmbedModelInterface extends Arrayable, \ArrayAccess, Jsonable, \JsonSerializable
{

    public function fill($attributes);

    public function isFillable($key): bool;

    public function setAttribute($key, $value);

    public function getAttribute($key): mixed;
}
