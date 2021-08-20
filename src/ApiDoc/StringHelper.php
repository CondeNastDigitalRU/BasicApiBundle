<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\ApiDoc;

use Symfony\Component\String\Slugger\AsciiSlugger;
use function Symfony\Component\String\u;

class StringHelper
{
    public static function toSentence(string $string): string
    {
        return (string) u(self::toWords($string))->title();
    }

    public static function toWords(string $string): string
    {
        return (string) u($string)->snake()->replace('_', ' ');
    }

    public static function slugify(string $string): string
    {
        return (string) (new AsciiSlugger())->slug($string);
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
