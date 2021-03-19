<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\ApiDoc;

final class Helper
{
    public static function camelCaseToSentence(string $camelCase): string
    {
        return \ucfirst(\trim(\preg_replace_callback(
            '/([A-Z])/',
            /**
             * @param array<int, string> $matches
             */
            static function (array $matches): string {
                return ' '.\lcfirst($matches[1]);
            },
            $camelCase
        )));
    }

    /**
     * @param mixed $value
     */
    public static function toString($value): string
    {
        switch (true) {
            case \is_scalar($value):
            case \is_object($value) && \method_exists($value, '__toString'):
                return (string) $value;
            case \is_null($value):
                return 'null';
            case \is_callable($value):
                return self::toString($value());
            case \is_array($value):
            case \is_object($value):
                return \json_encode($value);
            default:
                return '🤷';
        }
    }
}
