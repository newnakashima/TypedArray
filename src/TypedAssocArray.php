<?php declare(strict_types=1);

namespace Newnakashima\TypedArray;

use IteratorAggregate;
use InvalidArgumentException;
use Countable;
use Generator;
use Newnakashima\TypedArray\TypedArrayTrait;
use OutOfBoundsException;

class TypedAssocArray implements IteratorAggregate, Countable
{
    use TypedArrayTrait;

    private string $keyType;
    private array $keys = [];
    private bool $isKeyPrimitive;

    public function __construct(string $keyType, string $type, array $keys = [], array $items = [])
    {
        if (count($keys) !== count($items)) {
            throw new InvalidArgumentException('Keys and items must be of equal length');
        }

        $keyType = $this->primitiveMap[$keyType] ?? $keyType;
        $this->isKeyPrimitive = in_array($keyType, array_column(Primitives::cases(), 'value'));
        if (!$this->isKeyPrimitive) {
            try {
                new \ReflectionClass($keyType);
            } catch (\ReflectionException $e) {
                throw new InvalidArgumentException("Key type {$keyType} does not exist");
            }
        }

        $this->keyType = $keyType;

        if ($this->isKeyPrimitive) {
            foreach ($keys as $key) {
                if (gettype($key) !== $keyType) {
                    throw new InvalidArgumentException("Key must be of type {$keyType}");
                }

                $this->keys[] = $key;
            }
        } else {
            foreach ($keys as $key) {
                if ($key instanceof $this->keyType === false) {
                    try {
                        $key = new $this->keyType($key);
                    } catch (InvalidArgumentException $e) {
                        throw new InvalidArgumentException("Key must be of type {$this->keyType}");
                    }
                }

                $this->keys[] = $key;
            }
        }

        $this->initialize($type, $items);
    }

    private function validateKey(mixed $key): mixed
    {
        if ($this->isKeyPrimitive) {
            if (gettype($key) !== $this->keyType) {
                throw new InvalidArgumentException("Key must be of type {$this->keyType}");
            }
        } else {
            if ($key instanceof $this->keyType === false) {
                try {
                    $key = new $this->keyType($key);
                } catch (InvalidArgumentException $e) {
                    throw new InvalidArgumentException("Key must be of type {$this->keyType}");
                }
            }
        }

        return $key;
    }

    private function validateValue(mixed $item)
    {
        if ($this->isPrimitive) {
            if (gettype($item) !== $this->type) {
                throw new InvalidArgumentException("Item must be of type {$this->type}");
            }
        } else {
            if ($item instanceof $this->type === false) {
                try {
                    $item = new $this->type($item);
                } catch (InvalidArgumentException $e) {
                    throw new InvalidArgumentException("Item must be of type {$this->type}");
                }
            }
        }

        return $item;
    }

    private function validate(mixed $key, mixed $item): mixed
    {
        $key = $this->validateKey($key);
        $item = $this->validateValue($item);

        return ['key' => $key, 'item' => $item];
    }

    public function add($key, $item)
    {
        list('key' => $key, 'item' => $item) = $this->validate($key, $item);
        $this->keys[] = $key;
        $this->items[] = $item;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Generator
    {
        foreach ($this->items as $index => $item) {
            yield $this->keys[$index] => $item;
        }
    }

    public function exists($key): bool
    {
        $key = $this->validateKey($key);
        return in_array($key, $this->keys);
    }

    public function get($key): mixed
    {
        $key = $this->validateKey($key);
        $index = array_search($key, $this->keys);
        if ($index === false) {
            $keyExported = var_export($key, true);
            throw new OutOfBoundsException("Key does not exist: {$keyExported}");
        }

        return $this->items[$index];
    }

    public function getKeys(): array
    {
        return $this->keys;
    }

    public function getValues(): array
    {
        return $this->items;
    }

    public function unset($key): void
    {
        $key = $this->validateKey($key);
        $index = array_search($key, $this->keys);
        if ($index === false) {
            $keyExported = var_export($key, true);
            throw new InvalidArgumentException("Key does not exist: {$keyExported}");
        }

        unset($this->keys[$index]);
        unset($this->items[$index]);
        $this->keys = array_values($this->keys);
        $this->items = array_values($this->items);
    }

    public function filter(callable $callback): TypedAssocArray
    {
        $keys = [];
        $items = [];
        foreach ($this->items as $index => $item) {
            if ($callback($item)) {
                $keys[] = $this->keys[$index];
                $items[] = $item;
            }
        }

        return new TypedAssocArray($this->keyType, $this->type, $keys, $items);
    }

    public function filterWithKeys(callable $callback): TypedAssocArray
    {
        $keys = [];
        $items = [];
        foreach ($this->items as $index => $item) {
            if ($callback($this->keys[$index], $item)) {
                $keys[] = $this->keys[$index];
                $items[] = $item;
            }
        }

        return new TypedAssocArray($this->keyType, $this->type, $keys, $items);
    }

    public function mapWithKeys(callable $callback): array
    {
        $result = [];
        foreach ($this->items as $index => $item) {
            $result[] = $callback($this->keys[$index], $item);
        }

        return $result;
    }

    private function validateMappedKeyItem(mixed $result): mixed
    {
        if (is_array($result)) {
            $result = (object)($result);
        }
        if (property_exists($result, 'key') === false || property_exists($result, 'item') === false) {
            throw new InvalidArgumentException('Callback must return an object with properties "key" and "item"');
        }

        return $result;
    }

    public function mapWithKeysAndTypes(string $keyType, string $type, callable $callback): TypedAssocArray
    {
        $keys = [];
        $items = [];
        foreach ($this->items as $index => $item) {
            $result = $this->validateMappedKeyItem(
                $callback($this->keys[$index], $item)
            );
            $keys[] = $result->key;
            $items[] = $result->item;
        }

        return new TypedAssocArray($keyType, $type, $keys, $items);
    }

    public function mapWithKeysAndSameTypes(callable $callback): TypedAssocArray
    {
        $keys = [];
        $items = [];
        foreach ($this->items as $index => $item) {
            $result = $this->validateMappedKeyItem(
                $callback($this->keys[$index], $item)
            );
            $keys[] = $result->key;
            $items[] = $result->item;
        }

        return new TypedAssocArray($this->keyType, $this->type, $keys, $items);
    }

    public function merge(TypedAssocArray $assocArray): TypedAssocArray
    {
        $targetKeys = $assocArray->getKeys();
        $targetValues = $assocArray->getValues();
        $newKeys = $this->keys;
        $newItems = $this->items;

        foreach ($targetKeys as $index => $targetKey) {
            if ($this->exists($targetKey)) {
                // If key already exists, replace the value
                $existsIndex = array_search($targetKey, $newKeys);
                $newItems[$existsIndex] = $targetValues[$index];
            } else {
                $newKeys[] = $targetKey;
                $newItems[] = $targetValues[$index];
            }
        }

        return new TypedAssocArray($this->keyType, $this->type, $newKeys, $newItems);
    }

    public function eachWithKeys(callable $callback): void
    {
        foreach ($this->items as $index => $item) {
            $callback($this->keys[$index], $item);
        }
    }

    public function toArray()
    {
        $asArray = [];
        foreach ($this->keys as $key) {
            try {
                $strKey = (string)$key;
            } catch (\Error $e) {
                $strKey = get_class($key) . '@' . spl_object_id($key);
            }

            $value = $this->get($key);
            try {
                $strValue = (string)$value;
            } catch (\Error $e) {
                $strValue = (array)$value;
            } catch (\Error $e) {
                $strValue = get_class($value) . '@' . spl_object_id($value);
            }

            $asArray[$strKey] = $strValue;
        }

        return $asArray;
    }

    public function __toString()
    {
        $array = $this->toArray();
        return var_export($array, true);
    }
}
