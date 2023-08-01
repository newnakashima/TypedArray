<?php declare(strict_types=1);

namespace Newnakashima\TypedArray;

use InvalidArgumentException;
use Newnakashima\TypedArray\Primitives;

trait TypedArrayTrait
{
    protected string $type;
    protected array $items = [];
    protected bool $isPrimitive;
    protected array $primitiveMap = [
        'int' => 'integer',
        'float' => 'double'
    ];

    public function initialize(string $type, array $items)
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
}
