<?php declare(strict_types=1);

use Newnakashima\TypedArray\TypedAssocArray;

class KeyClass
{
    public int $value;
    public function __construct(int $value)
    {
        $this->value = $value;
    }
}

class ValueClass
{
    public int $value;
    public function __construct(int $value)
    {
        $this->value = $value;
    }
}

test('construct', function () {
    $data = [
        'one' => 'foo',
        'two' => 'bar'
    ];

    $assoc = new TypedAssocArray('string', 'string', array_keys($data), array_values($data));

    expect($assoc)
        ->toBeInstanceOf(TypedAssocArray::class)
        ->toHaveLength(2);

    $keys = [
        new KeyClass(1),
        new KeyClass(2),
    ];

    $values = [
        new ValueClass(1),
        new ValueClass(2),
    ];

    $assoc = new TypedAssocArray(KeyClass::class, ValueClass::class, $keys, $values);

    expect($assoc)
        ->toBeInstanceOf(TypedAssocArray::class)
        ->toHaveLength(2);
});

test('add', function () {
    $data = [
        'one' => 'foo',
        'two' => 'bar'
    ];

    $assoc = new TypedAssocArray('string', 'string', array_keys($data), array_values($data));
    $assoc->add('three', 'baz');

    expect($assoc)
        ->toBeInstanceOf(TypedAssocArray::class)
        ->toHaveLength(3);
});

test('getIterator', function () {
    $keys = [
        new KeyClass(1),
        new KeyClass(2),
    ];

    $values = [
        new ValueClass(1),
        new ValueClass(2),
    ];

    $assoc = new TypedAssocArray(KeyClass::class, ValueClass::class, $keys, $values);

    expect($assoc->getIterator())
        ->toBeInstanceOf(Generator::class);

    foreach ($assoc as $key => $value) {
        expect($key)->toBeInstanceOf(KeyClass::class);
        expect($value)->toBeInstanceOf(ValueClass::class);
    }
});

test('exists', function () {
    $keys = [
        new KeyClass(1),
        new KeyClass(2),
    ];

    $values = [
        new ValueClass(1),
        new ValueClass(2),
    ];

    $assoc = new TypedAssocArray(KeyClass::class, ValueClass::class, $keys, $values);

    expect($assoc->exists(new KeyClass(1)))->toBeTrue();
    expect($assoc->exists(new KeyClass(2)))->toBeTrue();
    expect($assoc->exists(1))->toBeTrue();
    expect($assoc->exists(2))->toBeTrue();
    expect($assoc->exists(new KeyClass(3)))->toBeFalse();
});

test('get', function () {
    $keys = [
        new KeyClass(1),
        new KeyClass(2),
    ];

    $values = [
        new ValueClass(1),
        new ValueClass(2),
    ];

    $assoc = new TypedAssocArray(KeyClass::class, ValueClass::class, $keys, $values);

    /** @var ValueClass */
    $value = $assoc->get(new KeyClass(1));
    expect($value)->toBeInstanceOf(ValueClass::class);
    expect($value->value)->toBe(1);
});

test('unset', function () {
    $keys = [
        new KeyClass(1),
        new KeyClass(2),
    ];

    $values = [
        new ValueClass(1),
        new ValueClass(2),
    ];

    $assoc = new TypedAssocArray(KeyClass::class, ValueClass::class, $keys, $values);

    $assoc->unset(new KeyClass(1));
    expect($assoc->exists(new KeyClass(1)))->toBeFalse();
});

test('filter', function () {
    $keys = [
        new KeyClass(1),
        new KeyClass(2),
    ];

    $values = [
        new ValueClass(1),
        new ValueClass(2),
    ];

    $assoc = new TypedAssocArray(KeyClass::class, ValueClass::class, $keys, $values);

    $filtered = $assoc->filter(function (ValueClass $value) {
        return $value->value === 1;
    });

    expect($filtered)
        ->toBeInstanceOf(TypedAssocArray::class)
        ->toHaveLength(1);
    expect($filtered->get(1)->value)->toBe(1);
    expect(function () use ($filtered) {
        $filtered->get(2);
    })->toThrow(OutOfBoundsException::class);
});

test('filterWithKeys', function () {
    $keys = [
        new KeyClass(1),
        new KeyClass(2),
    ];

    $values = [
        new ValueClass(1),
        new ValueClass(2),
    ];

    $assoc = new TypedAssocArray(KeyClass::class, ValueClass::class, $keys, $values);

    $filtered = $assoc->filterWithKeys(function (KeyClass $key, ValueClass $value) {
        return $key->value === 1 && $value->value === 1;
    });

    expect($filtered)
        ->toBeInstanceOf(TypedAssocArray::class)
        ->toHaveLength(1);
    expect($filtered->get(1)->value)->toBe(1);
    expect(function () use ($filtered) {
        $filtered->get(2);
    })->toThrow(OutOfBoundsException::class);
});

test('mapWithKeys', function () {
    $keys = [
        new KeyClass(1),
        new KeyClass(2),
    ];

    $values = [
        new ValueClass(3),
        new ValueClass(4),
    ];

    $assoc = new TypedAssocArray(KeyClass::class, ValueClass::class, $keys, $values);

    $mapped = $assoc->mapWithKeys(function (KeyClass $key, ValueClass $value) {
        return "key: {$key->value}, value: {$value->value}";
    });

    expect($mapped[0])->toBe('key: 1, value: 3');
    expect($mapped[1])->toBe('key: 2, value: 4');
});

test('mapWithKeysAndTypes', function () {
    $keys = [
        new KeyClass(1),
        new KeyClass(2),
    ];

    $values = [
        new ValueClass(3),
        new ValueClass(4),
    ];

    $assoc = new TypedAssocArray(KeyClass::class, ValueClass::class, $keys, $values);

    $mapped = $assoc->mapWithKeysAndTypes('string', 'string', function (KeyClass $key, ValueClass $value) {
        return [
            'key' => "key: {$key->value}",
            'item' => "value: {$value->value}",
        ];
    });

    expect($mapped->get('key: 1'))->toBe('value: 3');
    expect($mapped->get('key: 2'))->toBe('value: 4');

    $mapped = $assoc->mapWithKeysAndTypes('string', 'string', function (KeyClass $key, ValueClass $value) {
        $obj = new \stdClass();
        $obj->key = "key: {$key->value}";
        $obj->item = "value: {$value->value}";
        return $obj;
    });

    expect($mapped->get('key: 1'))->toBe('value: 3');
    expect($mapped->get('key: 2'))->toBe('value: 4');
});

test('mapWithKeysAndSameTypes', function () {
    $keys = [
        new KeyClass(1),
        new KeyClass(2),
    ];

    $values = [
        new ValueClass(3),
        new ValueClass(4),
    ];

    $assoc = new TypedAssocArray(KeyClass::class, ValueClass::class, $keys, $values);

    $mapped = $assoc->mapWithKeysAndSameTypes(function (KeyClass $key, ValueClass $value) {
        return [
            'key' => new KeyClass($key->value),
            'item' => new ValueClass($value->value * 2),
        ];
    });

    expect($mapped->get(1)->value)->toBe(6);
    expect($mapped->get(2)->value)->toBe(8);

    $mapped = $assoc->mapWithKeysAndSameTypes( function (KeyClass $key, ValueClass $value) {
        $obj = new \stdClass();
        $obj->key = new KeyClass($key->value);
        $obj->item = new ValueClass($value->value * 2);
        return $obj;
    });

    expect($mapped->get(1)->value)->toBe(6);
    expect($mapped->get(2)->value)->toBe(8);
});

test('merge', function () {
    $keys = [
        new KeyClass(1),
        new KeyClass(2),
    ];

    $values = [
        new ValueClass(3),
        new ValueClass(4),
    ];

    $assoc = new TypedAssocArray(KeyClass::class, ValueClass::class, $keys, $values);

    $merged = $assoc->merge(new TypedAssocArray(
        KeyClass::class,
        ValueClass::class,
        [
            new KeyClass(2),
            new KeyClass(3),
            new KeyClass(4),
        ], [
            new ValueClass(5),
            new ValueClass(6),
            new ValueClass(7),
        ]
    ));

    expect($merged->get(1)->value)->toBe(3);
    expect($merged->get(2)->value)->toBe(5);
    expect($merged->get(3)->value)->toBe(6);
    expect($merged->get(4)->value)->toBe(7);
});

test('eachWithKeys', function () {
    $keys = [
        new KeyClass(1),
        new KeyClass(2),
    ];

    $values = [
        new ValueClass(3),
        new ValueClass(4),
    ];

    $assoc = new TypedAssocArray(KeyClass::class, ValueClass::class, $keys, $values);
    $assoc->eachWithKeys(function ($key, $item) {
        expect($key)->toBeInstanceOf(KeyClass::class);
        expect($item)->toBeInstanceOf(ValueClass::class);
    });
});

test('find', function () {
    $keys = [
        new KeyClass(1),
        new KeyClass(2),
    ];

    $values = [
        new ValueClass(3),
        new ValueClass(4),
    ];

    $assoc = new TypedAssocArray(KeyClass::class, ValueClass::class, $keys, $values);
    $found = $assoc->find(function (ValueClass $value) {
        return $value->value === 3;
    });

    expect($found)->toBeInstanceOf(ValueClass::class);
    expect($found->value)->toBe(3);
});

test('isEmpty', function () {
    $list = new TypedAssocArray(KeyClass::class, ValueClass::class);
    expect($list->isEmpty())->toBe(true);
    $list->add(new KeyClass(1), new ValueClass(1));
    expect($list->isEmpty())->toBe(false);
});

class KeyClassWithToString extends KeyClass
{
    public function __toString()
    {
        return (string) $this->value;
    }
}

class ValueClassWithToString extends ValueClass
{
    public function __toString()
    {
        return (string) $this->value;
    }
}

test('toArray', function () {
    $keys = [
        new KeyClassWithToString(1),
        new KeyClassWithToString(2),
    ];

    $values = [
        new ValueClassWithToString(3),
        new ValueClassWithToString(4),
    ];

    $assoc = new TypedAssocArray(KeyClass::class, ValueClass::class, $keys, $values);

    expect($assoc->toArray())->toBe([
        '1' => '3',
        '2' => '4',
    ]);

    $keys = [
        new KeyClass(1),
        new KeyClass(2),
    ];

    $values = [
        new ValueClass(3),
        new ValueClass(4),
    ];

    $assoc = new TypedAssocArray(KeyClass::class, ValueClass::class, $keys, $values);

    $asArray = $assoc->toArray();
    $arrayKeys = array_keys($asArray);
    expect($arrayKeys[0])->toContain('KeyClass');
    expect($arrayKeys[0])->toContain('KeyClass');
    expect($asArray[$arrayKeys[0]])->toHaveKey('value');
    expect($asArray[$arrayKeys[1]])->toHaveKey('value');
});

test('toString', function () {
    $keys = [
        new KeyClassWithToString(1),
        new KeyClassWithToString(2),
    ];

    $values = [
        new ValueClassWithToString(3),
        new ValueClassWithToString(4),
    ];

    $assoc = new TypedAssocArray(KeyClass::class, ValueClass::class, $keys, $values);

    expect((string) $assoc)
        ->toContain('array')
        ->toContain('=>');
});
