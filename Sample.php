<?php

require_once __DIR__ . '/vendor/autoload.php';
use Newnakashima\TypedArray\TypedArray;
use Newnakashima\TypedArray\Primitives;

class Example1
{
    public int $value;
    public function __construct(int $value)
    {
        $this->value = $value;
    }
}

class Example2
{
    public int $value;
    public function __construct(int $value)
    {
        $this->value = $value;
    }
}

class Sample
{
    public function handle()
    {
        $length = 10000;
        $list = new TypedArray(Primitives::Int->value, range(1, $length));
        $empty = new TypedArray('int');
        foreach ($list as $item) {
            $empty->add($item * 2);
        }
        assert($empty->count() === $length);

        $mapped = $list->map(fn ($item) => $item * 2);
        assert($mapped[$length - 1], $length * 2);

        $filtered = $list->filter(fn ($item) => $item % 2 === 0);
        assert($filtered->count() === $length / 2);

        $filtered->each(fn ($item) => assert($item % 2 === 0));

        $exampleArray = new TypedArray(Example1::class, [
            new Example1(1),
            new Example1(2),
        ]);

        // This causes an error because the type is not matched.
        // $exampleArray->add(new Example2(3));

        // This is OK because the type is matched.
        $exampleArray->add(new Example1(3));

        // This also is OK. TypedArray will try to initialize the object with the given value.
        // Within add() method, Example1::__construct(3) is called.
        $exampleArray->add(3);

        assert($exampleArray[2]->value === 3);
        assert($exampleArray[2] instanceof Example1);
    }
}

$start = microtime(true);
(new Sample())->handle();
$elapsed = microtime(true) - $start;
echo "elapsed: {$elapsed} sec\n";
echo "memory: " . memory_get_peak_usage(true) / 1024 / 1024 . " MB\n";
