<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Annotation;

/**
 * @Annotation
 * @Target("ANNOTATION")
 * @Attributes({
 *     @Attribute("groups", type="array<string>"),
 *     @Attribute("sequence", type="bool")
 * })
 */
class Validation
{
    /** @var array<string> */
    private $groups;

    /** @var bool */
    private $sequence;

    public function __construct(array $values)
    {
        $this->groups = $values['groups'] ?? [];
        $this->sequence = $values['sequence'] ?? false;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function isSequence(): bool
    {
        return $this->sequence;
    }
}
