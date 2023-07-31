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
    expect($list[2])->toBe(null);
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
    expect($list[0])->toBeNull();
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
