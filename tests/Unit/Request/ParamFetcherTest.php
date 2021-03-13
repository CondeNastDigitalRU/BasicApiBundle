<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Unit\Request;

use Condenast\BasicApiBundle\Request\ParamFetcher;
use Condenast\BasicApiBundle\Request\ParamTypes;
use Condenast\BasicApiBundle\Tests\Unit\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class ParamFetcherTest extends TestCase
{
    use Assert;

    /**
     * @test
     * @dataProvider values
     */
    public function it_can_extract_values(string $type, $originalValue, $expectedValue, bool $map = false): void
    {
        $path = 'param';
        $parameters = [$path => $originalValue];
        $paramFetcher = new ParamFetcher($parameters, PropertyAccess::createPropertyAccessor());

        self::assertValuesEquals($expectedValue, $paramFetcher->get($path, $type, $map));
    }

    /**
     * @test
     * @dataProvider values
     */
    public function it_can_extract_nested_values(string $type, $originalValue, $expectedValue, bool $map = false): void
    {
        $path = 'param';
        $nestedPath = '[nestedParam]';
        $parameters = [$path => ['nestedParam' => $originalValue]];

        $paramFetcher = new ParamFetcher($parameters, PropertyAccess::createPropertyAccessor());

        self::assertValuesEquals($expectedValue, $paramFetcher->get($path.$nestedPath, $type, $map));
    }

    public function values(): array
    {
        return [
            'Int' => [ParamTypes::INT, '1', 1],
            'Float' => [ParamTypes::FLOAT, '1.0', 1.0],
            'Boolean 1' => [ParamTypes::BOOLEAN, '1', true],
            'Boolean true' => [ParamTypes::BOOLEAN, 'true', true],
            'Boolean 0' => [ParamTypes::BOOLEAN, '0', false],
            'Boolean false' => [ParamTypes::BOOLEAN, 'false', false],
            'String' => [ParamTypes::STRING, 'string', 'string'],
            'DateTime' => [ParamTypes::DATETIME, '2020-05-10 04:04:04', new \DateTime('2020-05-10 04:04:04')],
            'DateTimeImmutable' => [ParamTypes::DATETIME, '2020-05-10 04:04:04', new \DateTimeImmutable('2020-05-10 04:04:04')],
            'Mixed' => [ParamTypes::MIXED, ['mixed', ['mixed']], ['mixed', ['mixed']]],

            'Invalid float' => [ParamTypes::FLOAT, 'invalid float', null],
            'Invalid boolean' => [ParamTypes::BOOLEAN, 'invalid boolean', null],
            'Invalid string' => [ParamTypes::STRING, [], null],
            'Invalid DateTime' => [ParamTypes::DATETIME, 'invalid datetime', null],
            'Invalid DateTimeImmutable' => [ParamTypes::DATETIME, 'invalid datetimeimmutable', null],

            'Map of int' => [ParamTypes::INT, ['a' => '1', 'b' => '2'], ['a' => 1, 'b' => 2], true],
            'Map of float' => [ParamTypes::FLOAT, ['a' => '1.0', 'b' => '2.5'], ['a' => 1.0, 'b' => 2.5], true],
            'Map of boolean' => [ParamTypes::BOOLEAN, ['a' => '0', 'b' => 'false', 'c' => '1', 'd' => 'true'], ['a' => false, 'b' => false, 'c' => true, 'd' => true], true],
            'Map of string' => [ParamTypes::STRING, ['a' => 'string1', 'b' => 'string2'], ['a' => 'string1', 'b' => 'string2'], true],
            'Map of DateTime' => [ParamTypes::DATETIME, ['a' => '2020-05-10 04:04:04', 'b' => '2020-05-10 07:07:07'], ['a' => new \DateTime('2020-05-10 04:04:04'), 'b' => new \DateTime('2020-05-10 07:07:07')], true],
            'Map of DateTimeImmutable' => [ParamTypes::DATETIME_IMMUTABLE, ['a' => '2020-05-10 04:04:04', 'b' => '2020-05-10 07:07:07'], ['a' => new \DateTimeImmutable('2020-05-10 04:04:04'), 'b' => new \DateTimeImmutable('2020-05-10 07:07:07')], true],
            'Map of mixed' => [ParamTypes::MIXED, ['a' => 'mixed', 'b' => ['mixed']], ['a' => 'mixed', 'b' => ['mixed']], true],

            'Map of invalid float' => [ParamTypes::FLOAT, ['invalid float'], null, true],
            'Map of invalid boolean' => [ParamTypes::BOOLEAN, ['invalid boolean'], null, true],
            'Map of invalid string' => [ParamTypes::STRING, [[]], null, true],
            'Map of invalid DateTime' => [ParamTypes::DATETIME, [['invalid datetime']], null, true],
            'Map of invalid DateTimeImmutable' => [ParamTypes::DATETIME, [['invalid datetimeimmutable']], null, true],
            'Invalid map' => [ParamTypes::MIXED, 'invalid array', null, true],
        ];
    }

    /**
     * @test
     */
    public function extracting_an_unknown_type_results_in_an_error(): void
    {
        $paramFetcher = new ParamFetcher(['param' => 'value'], PropertyAccess::createPropertyAccessor());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown type "unknown type"');

        $paramFetcher->get('param', 'unknown type');
    }
}
