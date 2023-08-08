<?php declare(strict_types=1);

namespace Newnakashima\TypedArray;

use ArrayAccess;
use IteratorAggregate;
use InvalidArgumentException;
use Countable;
use Generator;
use OutOfBoundsException;

class TypedArray implements IteratorAggregate, Countable, ArrayAccess
{
    use TypedArrayTrait;

    public function __construct(string $type, array $items = [])
    {
        $this->initialize($type, $items);
    }

    public function getItemType(): string
    {
        return $this->type;
    }

    private function validate(mixed $item): mixed
    {
        if ($this->isPrimitive) {
            if (gettype($item) !== $this->type) {
                throw new InvalidArgumentException("Item must be of type {$this->type}");
            }
        } else if ($item instanceof $this->type === false) {
            try {
                $item = new $this->type($item);
            } catch (InvalidArgumentException $e) {
                throw new InvalidArgumentException("Item must be of type {$this->type}");
            }
        }

        return $item;
    }

    public function add($item)
    {
        $item = $this->validate($item);
        $this->items[] = $item;
    }

    public function getIterator(): Generator
    {
        foreach ($this->items as $item) {
            yield $item;
        }
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        if (!isset($this->items[$offset])) {
            throw new OutOfBoundsException("Offset {$offset} does not exist");
        }

        return $this->items[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $value = $this->validate($value);
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    public function filter(callable $callback): TypedArray
    {
        return new TypedArray($this->type, array_filter($this->items, $callback));
    }

    public function merge(TypedArray $list): TypedArray
    {
        if ($list->getItemType() !== $this->type) {
            throw new InvalidArgumentException("Item type must be {$this->type}");
        }
        return new TypedArray($this->type, array_merge($this->items, $list->items));
    }

    public function push(mixed $item): void
    {
        $item = $this->validate($item);
        array_push($this->items, $item);
    }

    public function pop(): mixed
    {
        return array_pop($this->items);
    }

    public function shift(): mixed
    {
        return array_shift($this->items);
    }

    public function unshift(mixed $item): void
    {
        $item = $this->validate($item);
        array_unshift($this->items, $item);
    }

    public function reverse(): TypedArray
    {
        return new TypedArray($this->type, array_reverse($this->items));
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function first(): mixed
    {
        return $this->items[0];
    }

    public function last(): mixed
    {
        return $this->items[$this->count() - 1];
    }
}
