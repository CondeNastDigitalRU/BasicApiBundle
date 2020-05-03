<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Unit\Util;

use Condenast\BasicApiBundle\Util\Sanitizer;
use PHPUnit\Framework\TestCase;

class SanitizerTest extends TestCase
{
    /**
     * @dataProvider isBinaryStringProvider
     */
    public function testIsBinaryString(string $string, bool $isBinary): void
    {
        $this->assertSame($isBinary, Sanitizer::isBinaryString($string));
    }

    public function isBinaryStringProvider(): array
    {
        return [
            [\file_get_contents(\dirname(__DIR__, 2).'/Fixtures/notBinaryString.txt'), false],
            [\base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+P+/HgAFhAJ/wlseKgAAAABJRU5ErkJggg=='), true],
        ];
    }

    public function testSanitize(): void
    {
        $this->assertSame(
            '!!binary iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+P+/HgAFhAJ/wlseKgAAAABJRU5ErkJggg==',
            Sanitizer::sanitize(\base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+P+/HgAFhAJ/wlseKgAAAABJRU5ErkJggg=='))
        );
    }

    public function testSanitizeRecursive(): void
    {
        $this->assertSame(
            [
                'nested1' => [
                    'nested2' => ['!!binary iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+P+/HgAFhAJ/wlseKgAAAABJRU5ErkJggg==']
                ]
            ],
            Sanitizer::sanitizeRecursive([
                'nested1' => [
                    'nested2' => [
                        \base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+P+/HgAFhAJ/wlseKgAAAABJRU5ErkJggg==')
                    ]
                ]
            ])
        );
    }
}
