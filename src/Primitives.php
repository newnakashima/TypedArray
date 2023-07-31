<?php declare(strict_types=1);

namespace Newnakashima\TypedArray;

enum Primitives: string
{
    case String = 'string';
    case Int = 'integer';
    case Float = 'double';
    case Bool = 'bool';
    case Array = 'array';
    case Object = 'object';
    case Null = 'NULL';
    case Unknown = 'unknown type';
}