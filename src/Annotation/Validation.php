<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Annotation;

/**
 * @Annotation
 * @Target("METHOD")
 * @Attributes({
 *     @Attribute("groups", type="array<string>")
 * })
 */
class Validation
{
    /** @var list<string> */
    private $groups;

    /**
     * @param array{groups: list<string>} $values
     */
    public function __construct(array $values)
    {
        $this->groups = $values['groups'] ?? [];
    }

    /**
     * @return array<string>
     */
    public function getGroups(): array
    {
        return $this->groups;
    }
}
