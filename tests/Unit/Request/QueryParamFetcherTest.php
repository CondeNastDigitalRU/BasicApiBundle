<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Unit\Request;

use Condenast\BasicApiBundle\Exception\RuntimeException;
use Condenast\BasicApiBundle\Request\QueryParamFetcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class QueryParamFetcherTest extends TestCase
{
    /**
     * @dataProvider getDataProvider
     */
    public function testGetFromRequest(?string $type, bool $isArray, $requestValue, $expectedValue): void
    {
        $propertyAccessor = $this->createMock(PropertyAccessor::class);
        $request = $this->createMock(Request::class);
        $query = $this->createMock(ParameterBag::class);
        $request->query = $query;
        $requestStack = $this->createConfiguredMock(RequestStack::class, [
            'getCurrentRequest' => $request,
        ]);

        $name = 'param';

        $query
            ->expects($this->once())
            ->method('get')
            ->with($name)
            ->willReturn($requestValue);

        $propertyAccessor
            ->expects($this->never())
            ->method('getValue');

        $queryParamFetcher = new QueryParamFetcher($requestStack, $propertyAccessor);
        $this->assertValuesEquals($expectedValue, $queryParamFetcher->get($name, null, $type, $isArray), $isArray);
    }

    /**
     * @dataProvider getDataProvider
     */
    public function testGetFromRequestNested(?string $type, bool $isArray, $requestValue, $expectedValue): void
    {
        $propertyAccessor = $this->createMock(PropertyAccessor::class);
        $request = $this->createMock(Request::class);
        $query = $this->createMock(ParameterBag::class);
        $request->query = $query;
        $requestStack = $this->createConfiguredMock(RequestStack::class, [
            'getCurrentRequest' => $request,
        ]);

        $name = 'param';
        $path = '[nestedParam]';
        $value = ['value'];

        $query
            ->expects($this->once())
            ->method('get')
            ->with($name)
            ->willReturn($value);

        $propertyAccessor
            ->expects($this->once())
            ->method('getValue')
            ->with($value, $path)
            ->willReturn($requestValue);

        $queryParamFetcher = new QueryParamFetcher($requestStack, $propertyAccessor);
        $this->assertValuesEquals($expectedValue, $queryParamFetcher->get($name, $path, $type, $isArray), $isArray);
    }

    private function assertValuesEquals($expectedValue, $actualValue, bool $isArray): void
    {
        if (null === $expectedValue) {
            $this->assertNull($actualValue);
        } elseif ($isArray) {
            $this->assertIsArray($actualValue);
            $this->assertEquals(\count($expectedValue), \count($actualValue));

            foreach ($expectedValue as $expectedKey => $expectedItem) {
                if (\is_object($expectedItem)) {
                    $this->assertEquals($expectedItem, $actualValue[$expectedKey]);
                } else {
                    $this->assertSame($expectedItem, $actualValue[$expectedKey]);
                }
            }
        } else {
            if (\is_object($expectedValue)) {
                $this->assertEquals($expectedValue, $actualValue);
            } else {
                $this->assertSame($expectedValue, $actualValue);
            }
        }
    }

    public function testGetNoCurrentRequestException(): void
    {
        $propertyAccessor = $this->createMock(PropertyAccessor::class);
        $requestStack = $this->createConfiguredMock(RequestStack::class, [
            'getCurrentRequest' => null,
        ]);

        $queryParamFetcher = new QueryParamFetcher($requestStack, $propertyAccessor);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('There is no current request');

        $queryParamFetcher->get('param');
    }

    public function testGetUnknownTypeException(): void
    {
        $propertyAccessor = $this->createMock(PropertyAccessor::class);
        $request = $this->createMock(Request::class);
        $query = $this->createMock(ParameterBag::class);
        $request->query = $query;
        $requestStack = $this->createConfiguredMock(RequestStack::class, [
            'getCurrentRequest' => $request,
        ]);
        $query
            ->method('get')
            ->willReturn('value');

        $queryParamFetcher = new QueryParamFetcher($requestStack, $propertyAccessor);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unknown type "unknown type"');

        $queryParamFetcher->get('param', null, 'unknown type');
    }

    public function getDataProvider(): array
    {
        return [
            'Int' => [QueryParamFetcher::TYPE_INT, false, '1', 1],
            'Float' => [QueryParamFetcher::TYPE_FLOAT, false, '1.0', 1.0],
            'Boolean 1' => [QueryParamFetcher::TYPE_BOOLEAN, false, '1', true],
            'Boolean true' => [QueryParamFetcher::TYPE_BOOLEAN, false, 'true', true],
            'Boolean 0' => [QueryParamFetcher::TYPE_BOOLEAN, false, '0', false],
            'Boolean false' => [QueryParamFetcher::TYPE_BOOLEAN, false, 'false', false],
            'String' => [QueryParamFetcher::TYPE_STRING, false, 'value', 'value'],
            'No type' => [null, false, 'value', 'value'],
            'DateTime' => [QueryParamFetcher::TYPE_DATETIME, false, '2020-05-10 04:04:04', new \DateTime('2020-05-10 04:04:04')],
            'DateTimeImmutable' => [QueryParamFetcher::TYPE_DATETIME, false, '2020-05-10 04:04:04', new \DateTimeImmutable('2020-05-10 04:04:04')],

            'Bad Int' => [QueryParamFetcher::TYPE_INT, false, 'not int', null],
            'Bad Float' => [QueryParamFetcher::TYPE_FLOAT, false, 'not float', null],
            'Bad Boolean' => [QueryParamFetcher::TYPE_BOOLEAN, false, 10, null],
            'Bad String' => [QueryParamFetcher::TYPE_STRING, false, [], null],
            'Bad No type' => [null, false, [], null],
            'Bad DateTime' => [QueryParamFetcher::TYPE_DATETIME, false, ['not datetime'], null],
            'Bad DateTimeImmutable' => [QueryParamFetcher::TYPE_DATETIME, false, ['not datetimeimmutable'], null],

            'Array of Int' => [QueryParamFetcher::TYPE_INT, true, ['a' => '1', 'b' => '2'], ['a' => 1, 'b' => 2]],
            'Array of Float' => [QueryParamFetcher::TYPE_FLOAT, true, ['a' => '1.0', 'b' => '2.5'], ['a' => 1.0, 'b' => 2.5]],
            'Array of Boolean' => [QueryParamFetcher::TYPE_BOOLEAN, true, ['a' => '0', 'b' => 'false', 'c' => '1', 'd' => 'true'], ['a' => false, 'b' => false, 'c' => true, 'd' => true]],
            'Array of String' => [QueryParamFetcher::TYPE_STRING, true, [1 => 'value1', 2 => 'value2'], [1 => 'value1', 2 => 'value2']],
            'Array of No type' => [null, true, [1 => 'value1', 2 => 'value2'], [1 => 'value1', 2 => 'value2']],
            'Array of DateTime' => [QueryParamFetcher::TYPE_DATETIME, true, [1 => '2020-05-10 04:04:04', 2 => '2020-05-10 07:07:07'], [1 => new \DateTime('2020-05-10 04:04:04'), 2 => new \DateTime('2020-05-10 07:07:07')]],
            'Array of DateTimeImmutable' => [QueryParamFetcher::TYPE_DATETIME, true, [1 => '2020-05-10 04:04:04', 2 => '2020-05-10 07:07:07'], [1 => new \DateTimeImmutable('2020-05-10 04:04:04'), 2 => new \DateTimeImmutable('2020-05-10 07:07:07')]],

            'Bad array of Int' => [QueryParamFetcher::TYPE_INT, true, '1', null],
            'Bad array of Float' => [QueryParamFetcher::TYPE_FLOAT, true, ['1.0', 'not float'], null],
            'Bad array of Boolean' => [QueryParamFetcher::TYPE_BOOLEAN, true, ['0', 'false', '1', 'not boolean'], null],
            'Bad array of String' => [QueryParamFetcher::TYPE_STRING, true, ['value1', ['value2']], null],
            'Bad array of No type' => [null, true, ['value1', ['value2']], null],
            'Bad array of DateTime' => [QueryParamFetcher::TYPE_DATETIME, true, ['2020-05-10 04:04:04', ['not datetime']], null],
            'Bad array of DateTimeImmutable' => [QueryParamFetcher::TYPE_DATETIME, true, ['2020-05-10 04:04:04', ['not datetimeimmutable']], null],
        ];
    }
}
