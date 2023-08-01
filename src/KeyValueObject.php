<?php declare(strict_types=1);

namespace Newnakashima\TypedArray;

class KeyValueObject
{
    public mixed $key;
    public mixed $item;

    public function __construct($key, $item)
    {
        $this->key = $key;
        $this->item = $item;
    }
}
