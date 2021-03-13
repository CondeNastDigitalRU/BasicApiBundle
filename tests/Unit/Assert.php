<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Unit;

trait Assert
{
    private static function assertValuesEquals($expectedValue, $actualValue): void
    {
        if (\is_array($expectedValue)) {
            self::assertIsArray($actualValue);
            self::assertCount(\count($expectedValue), $actualValue);

            foreach ($expectedValue as $expectedKey => $expectedItem) {
                self::assertArrayHasKey($expectedKey, $actualValue);
                self::assertValuesEquals($expectedItem, $actualValue[$expectedKey]);
            }
        } elseif (\is_object($expectedValue)) {
            self::assertEquals($expectedValue, $actualValue);
        } else {
            self::assertSame($expectedValue, $actualValue);
        }
    }
}
