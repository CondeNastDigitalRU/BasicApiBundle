<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Validation
{
    /**
     * @param array<string> $groups
     */
    public function __construct(public readonly array $groups = [])
    {
    }
}
