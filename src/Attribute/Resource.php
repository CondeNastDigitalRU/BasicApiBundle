<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Resource
{
    public function __construct(public readonly string $name)
    {
        '' === $this->name && throw new \InvalidArgumentException('The resource name must be a non-empty string');
    }
}
