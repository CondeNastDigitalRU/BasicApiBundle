<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Deserialization
{
    /**
     * @param array<string, string> $requestAttributes
     */
    public function __construct(
        public readonly string $argument,
        public readonly string $type,
        public readonly array $context = [],
        public readonly array $requestAttributes = [],
    ) {
        '' === $argument && throw new \InvalidArgumentException('The "argument" must be a non-empty string');
        '' === $type && throw new \InvalidArgumentException('The "type" must be a non-empty string');
    }
}
