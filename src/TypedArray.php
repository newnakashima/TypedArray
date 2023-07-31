<?php declare(strict_types=1);

namespace Newnakashima\TypedArray;

use ArrayAccess;
use IteratorAggregate;
use InvalidArgumentException;
use Countable;
use Generator;

class TypedArray implements IteratorAggregate, Countable, ArrayAccess
{
    private string $type;
    private array $items = [];
    private bool $isPrimitive;

    private array $primitiveMap = [
        'int' => 'integer',
        'float' => 'double'
    ];

    public function __construct(string $type, array $items = [])
    {
        $type = $this->primitiveMap[$type] ?? $type;
        $this->isPrimitive = in_array($type, array_column(Primitives::cases(), 'value'));
        if (!$this->isPrimitive) {
            try {
                new \ReflectionClass($type);
            } catch (\ReflectionException $e) {
                throw new InvalidArgumentException("Type {$type} does not exist");
            }
        }

        $this->type = $type;

        if ($this->isPrimitive) {
            foreach ($items as $item) {
                if (gettype($item) !== $type) {
                    throw new InvalidArgumentException("Item must be of type {$type}");
                }

                $this->items[] = $item;
            }
            return;
        }

        foreach ($items as $item) {
            if ($item instanceof $this->type === false) {
                try {
                    $item = new $this->type($item);
                } catch (InvalidArgumentException $e) {
                    throw new InvalidArgumentException("Item must be of type {$this->type}");
                }
            }

            $this->items[] = $item;
        }
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
        return $this->items[$offset] ?? null;
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

    public function map(callable $callback): array
    {
        return array_map($callback, $this->items);
    }

    public function mapWithType(string $type, callable $callback): TypedArray
    {
        return new TypedArray($type, array_map($callback, $this->items));
    }

    public function mapWithSameType(callable $callback): TypedArray
    {
        return $this->mapWithType($this->type, $callback);
    }

    public function merge(TypedArray $list): TypedArray
    {
        if ($list->getItemType() !== $this->type) {
            throw new InvalidArgumentException("Item type must be {$this->type}");
        }
        return new TypedArray($this->type, array_merge($this->items, $list->items));
    }

    public function each(callable $callback)
    {
        foreach ($this->items as $item) {
            $callback($item);
        }
    }
}
