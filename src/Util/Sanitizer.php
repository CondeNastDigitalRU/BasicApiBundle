<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Util;

class Sanitizer
{
    /**
     * Recursively replaces binary strings with their base64 equivalent in the array
     */
    public static function sanitizeRecursive(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (\is_array($value)) {
                $sanitized[$key] = self::sanitizeRecursive($value);
            } elseif (\is_string($value)) {
                $sanitized[$key] = self::sanitize($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Replaces binary string with its base64 equivalent
     */
    public static function sanitize(string $value): string
    {
        if (self::isBinaryString($value)) {
            return '!!binary '.\base64_encode($value);
        }

        return $value;
    }

    public static function isBinaryString(string $value): bool
    {
        return !\preg_match('//u', $value) || \preg_match('/[^\x00\x07-\x0d\x1B\x20-\xff]/', $value);
    }
}
