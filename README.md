# TypedArray

Typed Array for PHP.

TypedArray object can be treated as an array.
But it has a same type for every element.

For example, a TypedArray object which has string type does not allow developers to add an integer value to it.

```php
$stringArray = new TypedArray('string', ['foo', 'bar']);

// This causes an error.
$stringArray[] = 42;

// This is OK.
$stringArray[] = 'baz';
```

## Example

### Initialize
```php
$length = 10000;
# with initial value.
$list = new TypedArray(Primitives::Int->value, range(1, $length));

# without initial value.
$empty = new TypedArray('int');
foreach ($list as $item) {
    $empty->add($item * 2);
}
assert($empty->count() === $length);
```

### primitive types

#### integer
```php
$intArray = new TypedArray(Primitives::Int->value, range(1, 100));
# or
$intArray = new TypedArray('int', range(1, 100));
# or
$intArray = new TypedArray('integer', range(1, 100));
```

#### float(double)
```php
$doubleArray = new TypedArray(Primitives::Float->value, [0.1, 1.2]);
#or
$doubleArray = new TypedArray('float', [0.1, 1.2]);
#or
$doubleArray = new TypedArray('double', [0.1, 1.2]);
```

#### string
```php
$stringArray = new TypedArray(Primitives::String->value, ['foo', 'bar']);
# or
$stringArray = new TypedArray('string', ['foo', 'bar']);
```

#### bool
```php
$boolArray = new TypedArray(Primitives::Bool->value, [true, false]);
# or
$boolArray = new TypedArray('bool', [true, false]);
```

#### array
```php
$arrayArray = new TypedArray(Primitives::Array->vale, [
    [1, 2, 3],
    ['foo', 'bar', 'baz'],
]);
# or
$arrayArray = new TypedArray('array', [
    [1, 2, 3],
    ['foo', 'bar', 'baz'],
]);
```

#### object
```php
$obj1 = new \stdClass();
$obj2 = new \stdClass();
$objectArray = new TypedArray(Primitives::Object->value, [$obj1, $obj2]);
# or
$objectArray = new TypedArray('object', [$obj1, $obj2]);
```

#### other classes or interfaces
```php
class Foo
{
    public int $value = 0;
    public function __construct($value)
    {
        $this->value = $value;
    }
}

$fooArray = new TypedArray(Foo::class, [
    new Foo(1),
    new Foo(2),
]);
```

If the class takes only one argument for constructor, you can omit `new` keyword and class name.

```php
$fooArray = new TypedArray(Foo::class, [1, 2]);
assert(get_class($fooArray[0]) == Foo::class);
```

### add value

```php
$list = new TypedArray('string');
$list[] = 'foo';
# or
$list->add('foo');
```

### map
This method returns a primitive array. Not a TypedArray object.
```php
$mapped = $list->map(fn ($item) => $item * 2);
assert($mapped[$length - 1], $length * 2);
```

### mapWithType

This method returns another TypedArray which has a specific type.

```php
$list = new TypedArray('string', ['a', 'bb', 'cccc']);
$mapped = $list->mapWithType('int', fn ($item) => strlen($item));
assert(get_class($mapped) === TypedArray::class);
assert($mapped[0] === 1);
assert($mapped[1] === 2);
assert($mapped[2] === 4);
```

### mapWithSameType

```php
$list = new TypedArray('string', ['a', 'bb', 'cccc']);
$mapped = $list->mapWithSameType(fn ($item) => $item . '!');
assert(get_class($mapped) === TypedArray::class);
assert($mapped[0] === 'a!');
assert($mapped[1] === 'bb!');
assert($mapped[2] === 'cccc!');
```
### filter
```php
$filtered = $list->filter(fn ($item) => $item % 2 === 0);
assert($filtered->count() === $length / 2);
```

### each
```php
$filtered->each(fn ($item) => assert($item % 2 === 0));
```

### merge

```php
$list = new TypedArray('string', ['foo', 'bar']);
$anotherList = new TypedArray('string', ['baz', 'qux']);
$list = $list->merge($anotherList);
assert($list->count() === 4);
assert($list[0] === 'foo');
assert($list[1] === 'bar');
assert($list[2] === 'baz');
assert($list[3] === 'qux');
```

### find

```php
$list = new TypedArray('string', ['foo', 'bar']);
$found = $list->find(fn ($item) => $item === 'bar');
assert($found === 'bar');
```

### push
Wrapper of array_push() function.
```php
$list = new TypedArray('string', ['foo', 'bar']);
$list->push('baz', 'qux');
assert($list->count() === 4);
assert($list[2] === 'baz');
assert($list[3] === 'qux');
```

### pop

```php
$list = new TypedArray('string', ['foo', 'bar', 'baz']);
$last = $list->pop();
assert($last === 'baz');
assert($list->count() === 2);
```

### shift
```php
$list = new TypedArray('string', ['foo', 'bar', baz]);
$first = $list->shift();
assert($first === 'foo');
assert($list->count() === 2);
```

### unshift
```php
$list = new TypedArray('string', ['foo', 'bar']);
$list->unshift('baz', 'qux');
assert($list->count() === 4);
assert($list[0] === 'baz');
```

### reverse
```php
$list = new TypedArray('string', ['foo', 'bar', 'baz']);
$reversed = $list->reverse();
assert($reversed[0] === 'baz');
assert($reversed[1] === 'bar');
assert($reversed[2] === 'foo');
```

### first
```php
$list = new TypedArray('string', ['foo', 'bar', 'baz']);
$first = $list->first();
assert($first === 'foo');
```

### last
```php
$list = new TypedArray('string', ['foo', 'bar', 'baz']);
$last = $list->last();
assert($last === 'baz');
```

### toArray
```php
$list = new TypedArray('string', ['foo', 'bar', 'baz']);
$primitiveArray = $list->toArray();
assert(is_array($primitiveArray));
var_dump($primitiveArray);
/**
 * array(3) {
 *   [0]=>
 *   string(3) "foo"
 *   [1]=>
 *   string(3) "bar"
 *   [2]=>
 *   string(3) "baz"
 * }
 */
```

### __toString

This method returns a string representation for debugging.

```php
$list = new TypedArray('string', ['foo', 'bar', 'baz']);
$string = (string)$list
echo $string;
/**
 * string(51) "array (
 *   0 => 'foo',
 *   1 => 'bar',
 *   2 => 'baz',
 * )"
 */
```

## Auto initialization

If you pass a value which is not an instance of the given type, TypedArray will try to initialize the object with the given value.

```php
$exampleArray = new TypedArray(Foo::class, [
    new Foo(1),
    new Foo(2),
]);

// This causes an error because the type is not matched.
$exampleArray->add(new Bar(3));

// This is OK because the type is matched.
$exampleArray->add(new Foo(3));

// This is OK too. TypedArray will try to initialize the object with the given value.
// Within add() method, Foo::__construct(3) is called.
$exampleArray->add(3);

assert($exampleArray[2]->value === 3);
assert($exampleArray[2] instanceof Foo);
```
