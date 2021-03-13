<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Annotation;

/**
 * @Annotation
 * @Target("METHOD")
 * @Attributes({
 *     @Attribute("value", type="string", required=true),
 * })
 */
class Resource
{
    /** @var string */
    private $name;

    /**
     * @param array{value: string} $values
     */
    public function __construct(array $values)
    {
        if ('' === $values['value']) {
            throw new \InvalidArgumentException('The resource name must be a non-empty string');
        }
        $this->name = $values['value'];
    }

    public function getName(): string
    {
        return $this->name;
    }
}
