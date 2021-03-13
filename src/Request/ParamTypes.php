<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Request;

class ParamTypes
{
    public const INT = 'int';
    public const FLOAT = 'float';
    public const BOOLEAN = 'bool';
    public const STRING = 'string';
    public const DATETIME = 'datetime';
    public const DATETIME_IMMUTABLE = 'datetimeimmutable';
    public const MIXED = 'mixed';

    public const TYPES = [
        self::INT,
        self::FLOAT,
        self::BOOLEAN,
        self::STRING,
        self::DATETIME,
        self::DATETIME_IMMUTABLE,
        self::MIXED,
    ];
}
