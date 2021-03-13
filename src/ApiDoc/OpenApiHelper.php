<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\ApiDoc;

use Condenast\BasicApiBundle\Request\ParamTypes;

class OpenApiHelper
{
    private const TYPES = [
        ParamTypes::INT => 'integer',
        ParamTypes::FLOAT => 'number',
        ParamTypes::BOOLEAN => 'boolean',
        ParamTypes::STRING => 'string',
        ParamTypes::DATETIME => 'string',
        ParamTypes::DATETIME_IMMUTABLE => 'string',
        ParamTypes::MIXED => null,
    ];

    private const FORMATS = [
        ParamTypes::DATETIME => 'date-time',
        ParamTypes::DATETIME_IMMUTABLE => 'date-time',
    ];

    public static function convertParamType(string $paramType): ?string
    {
        return self::TYPES[$paramType] ?? null;
    }

    public static function getFormatForParamType(string $paramType): ?string
    {
        return self::FORMATS[$paramType] ?? null;
    }
}
