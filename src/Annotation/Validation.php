<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Annotation;

/**
 * @Annotation
 * @Target("ANNOTATION")
 * @Attributes({
 *     @Attribute("groups", type="array<string>")
 * })
 */
class Validation
{
    /** @var string[] */
    private $groups;

    public function __construct(array $values)
    {
        $this->groups = $values['groups'] ?? [];
    }

    public function getGroups(): array
    {
        return $this->groups;
    }
}
