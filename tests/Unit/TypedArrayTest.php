<?php declare(strict_types=1);

use Newnakashima\TypedArray\TypedArray;
use Newnakashima\TypedArray\Primitives;

class TestClass
{
    public mixed $value;
    public function __construct(mixed $value)
    {
        $this->value = $value;
    }
}

interface TestInterface
{
    public function foo(): string;
}

test('construct', function () {
    $list = new TypedArray('string', ['foo', 'bar']);

    expect($list)
        ->toBeInstanceOf(TypedArray::class)
        ->toHaveLength(2);
    expect($list[0])->toBe('foo');
    expect($list[1])->toBe('bar');

    expect(function () {
        new TypedArray('string', ['foo', 2]);
    })->toThrow(InvalidArgumentException::class);

    $list = new TypedArray('int', [1, 2]);
    expect($list)
        ->toBeInstanceOf(TypedArray::class)
        ->toHaveLength(2);

    expect(function () {
        new TypedArray('inte', [1, 2]);
    })->toThrow(InvalidArgumentException::class);
});

test('extended', function () {
    $object = new class('foo') extends TestClass {};

    $list = new TypedArray(TestClass::class, [$object]);
    expect($list)->toHaveLength(1);

    $object2 = new class('bar') extends TestClass {};
    $list->add($object2);
    expect($list)->toHaveLength(2);
});

test('interface', function () {
    $object = new class implements TestInterface {
        public function foo(): string
        {
            return 'foo';
        }
    };

    $list = new TypedArray(TestInterface::class, [$object]);
    expect($list)->toHaveLength(1);

    $object2 = new class implements TestInterface {
        public function foo(): string
        {
            return 'bar';
        }
    };
    $list->add($object2);
    expect($list)->toHaveLength(2);
});

test('add', function () {
    $list = new TypedArray('string');
    $list->add('foo');
    $list->add('bar');

    expect($list)
        ->toBeInstanceOf(TypedArray::class)
        ->toHaveLength(2);
    expect($list[0])->toBe('foo');
    expect($list[1])->toBe('bar');

    expect(function () {
        $list = new TypedArray('string');
        $list->add(2);
    })->toThrow(InvalidArgumentException::class);

    $list = new TypedArray(TestClass::class, [
        new TestClass('foo'),
        new TestClass('bar')
    ]);

    expect($list)
        ->toBeInstanceOf(TypedArray::class)
        ->toHaveLength(2);
});

test('getItemType', function () {
    $list = new TypedArray('string');
    expect($list->getItemType())->toBe('string');

    $list = new TypedArray(TestClass::class, [
        new TestClass('foo'),
        new TestClass('bar')
    ]);
    expect($list->getItemType())->toBe(TestClass::class);
});

test('count', function () {
    $list = new TypedArray('string', ['foo', 'bar']);
    expect($list->count())->toBe(2);
    $list->add('baz');
    expect($list->count())->toBe(3);
});

test('offsetExists', function () {
    $list = new TypedArray('string', []);
    expect(empty($list[0]))->toBe(true);
    expect(isset($list[0]))->toBe(false);
    $list->add('foo');
    expect(empty($list[0]))->toBe(false);
    expect(isset($list[0]))->toBe(true);
});

test('offsetGet', function () {
    $list = new TypedArray('string', ['foo', 'bar']);
    expect($list[0])->toBe('foo');
    expect($list[1])->toBe('bar');
    expect(function () use ($list) {
        $list[2];
    })->toThrow(OutOfBoundsException::class);
});

test('offsetSet', function () {
    expect(function () {
        $list = new TypedArray('string', ['foo', 'bar']);
        $list[1] = 100;
    })->toThrow(InvalidArgumentException::class);
    expect(function () {
        $list = new TypedArray('string', ['foo', 'bar']);
        $list[] = 100;
    })->toThrow(InvalidArgumentException::class);

    $list = new TypedArray('string', ['foo', 'bar']);
    $list[] = 'baz';
    expect($list)->toHaveLength(3);
    expect($list[2])->toBe('baz');

    $list[1] = 'qux';
    expect($list[1])->toBe('qux');
    expect($list)->toHaveLength(3);
});

test('offsetUnset', function () {
    $list = new TypedArray('string', ['foo', 'bar']);
    unset($list[0]);
    expect($list)->toHaveLength(1);
    expect(function () use ($list) {
        $list[0];
    })->toThrow(OutOfBoundsException::class);
    expect($list[1])->toBe('bar');
});

test('filter', function () {
    $list = new TypedArray('string', ['foo', 'bar', 'baz']);
    $list = $list->filter(function ($item) {
        return $item !== 'bar';
    });
    expect($list)->toHaveLength(2);
    expect($list[0])->toBe('foo');
    expect($list[1])->toBe('baz');
});

test('map', function () {
    $list = new TypedArray('string', ['foo', 'bar', 'baz']);
    $list = $list->map(function ($item) {
        return $item . '!';
    });
    expect($list)->toHaveLength(3);
    expect($list[0])->toBe('foo!');
    expect($list[1])->toBe('bar!');
    expect($list[2])->toBe('baz!');
});

test('mapWithType', function () {
    $list = new TypedArray('string', ['a', 'bb', 'cccc']);
    $list = $list->mapWithType('int', function ($item) {
        return strlen($item);
    });
    expect($list)->toHaveLength(3);
    expect($list[0])->toBe(1);
    expect($list[1])->toBe(2);
    expect($list[2])->toBe(4);

    expect(function () {
        $list = new TypedArray('string', ['a', 'bb', 'cccc']);
        $mapped = $list->mapWithType('int', function ($item) {
            return $item . '!';
        });
    })->toThrow(InvalidArgumentException::class);
});

test('mapWithSameType', function () {
    $list = new TypedArray('string', ['a', 'bb', 'cccc']);
    $list = $list->mapWithSameType(function ($item) {
        return $item . '!';
    });
    expect($list)->toHaveLength(3);
    expect($list[0])->toBe('a!');
    expect($list[1])->toBe('bb!');
    expect($list[2])->toBe('cccc!');

    expect(function () {
        $list = new TypedArray('string', ['a', 'bb', 'cccc']);
        $mapped = $list->mapWithSameType(function ($item) {
            return strlen($item);
        });
    })->toThrow(InvalidArgumentException::class);
});

test('merge', function () {
    $list = new TypedArray('string', ['foo', 'bar']);
    $list = $list->merge(new TypedArray('string', ['baz', 'qux']));
    expect($list)->toHaveLength(4);
    expect($list[0])->toBe('foo');
    expect($list[1])->toBe('bar');
    expect($list[2])->toBe('baz');
    expect($list[3])->toBe('qux');

    expect(function () {
        $list = new TypedArray('string', ['foo', 'bar']);
        $list = $list->merge(new TypedArray('int', [1, 2]));
    })->toThrow(InvalidArgumentException::class);
});

test('each', function () {
    $list = new TypedArray(Primitives::Int->value, range(1, 5));

    $a = [];
    $list->each(function ($item) use (&$a) {
        $a[] = $item;
    });

    expect($list)->toHaveLength(5);
    expect($a)->toHaveLength(5);
});

test('find', function () {
    $list = new TypedArray(Primitives::Int->value, range(1, 5));

    $item = $list->find(function ($item) {
        return $item === 3;
    });

    expect($item)->toBe(3);

    $list = new TypedArray(TestClass::class, [
        new TestClass('foo'),
        new TestClass('bar')
    ]);

    $item = $list->find(function (TestClass $item) {
        return $item->value === 'bar';
    });

    expect($item->value)->toBe('bar');
    expect($item)->toBeInstanceOf(TestClass::class);
});

test('push', function () {
    $list = new TypedArray(Primitives::Int->value, range(1, 5));
    $list->push(6, 7, 8);
    expect($list)->toHaveLength(8);
    expect($list[5])->toBe(6);
    expect($list[6])->toBe(7);
    expect($list[7])->toBe(8);
});

test('pop', function () {
    $list = new TypedArray(Primitives::Int->value, range(1, 5));
    $item = $list->pop();
    expect($list)->toHaveLength(4);
    expect($item)->toBe(5);
});

test('shift', function () {
    $list = new TypedArray(Primitives::Int->value, range(1, 5));
    $item = $list->shift();
    expect($list)->toHaveLength(4);
    expect($item)->toBe(1);
});

test('unshift', function () {
    $list = new TypedArray(Primitives::Int->value, range(1, 5));
    $list->unshift(0, -1, -2);
    expect($list)->toHaveLength(8);
    expect($list[0])->toBe(0);
    expect($list[1])->toBe(-1);
    expect($list[2])->toBe(-2);
});

test('isEmpty', function () {
    $list = new TypedArray(Primitives::Int->value, range(1, 5));
    expect($list->isEmpty())->toBe(false);
    $list = new TypedArray(Primitives::Int->value);
    expect($list->isEmpty())->toBe(true);
});

test('reverse', function () {
    $list = new TypedArray(Primitives::String->value, [
        'foo',
        'bar',
        'baz',
    ]);

    $list = $list->reverse();
    expect($list)->toHaveLength(3);
    expect($list[0])->toBe('baz');
    expect($list[1])->toBe('bar');
    expect($list[2])->toBe('foo');
});

test('toArray', function () {
    $list = new TypedArray(Primitives::String->value, [
        'foo',
        'bar',
        'baz',
    ]);

    $array = $list->toArray();
    expect($array)->toHaveLength(3);
    expect($array[0])->toBe('foo');
    expect($array[1])->toBe('bar');
    expect($array[2])->toBe('baz');
    expect($array)->toBeArray();
});

test('first', function () {
    $list = new TypedArray(Primitives::String->value, [
        'foo',
        'bar',
        'baz',
    ]);

    $item = $list->first();
    expect($item)->toBe('foo');
});

test('last', function () {
    $list = new TypedArray(Primitives::String->value, [
        'foo',
        'bar',
        'baz',
    ]);

    $item = $list->last();
    expect($item)->toBe('baz');
});

test('toString', function () {
    $list = new TypedArray(Primitives::String->value, [
        'foo',
        'bar',
        'baz',
    ]);

    $string = (string)$list;
    expect($string)
        ->toBeString()
        ->toContain('array')
        ->toContain(' => ');
});

test('unique', function () {
    $list = new TypedArray(Primitives::String->value, [
        'foo',
        'bar',
        'baz',
        'foo',
        'bar',
        'baz',
    ]);

    $list = $list->unique();
    expect($list)->toHaveLength(3);
    expect($list[0])->toBe('foo');
    expect($list[1])->toBe('bar');
    expect($list[2])->toBe('baz');
});
