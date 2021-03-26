<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Annotation;

/**
 * @Annotation
 * @Target("METHOD")
 * @Attributes({
 *     @Attribute("argument", type="string", required=true),
 *     @Attribute("type", type="string", required=true),
 *     @Attribute("context", type="array"),
 *     @Attribute("requestAttributes", type="array"),
 * })
 */
class Deserialization
{
    /** @var string */
    private $argument;

    /** @var string */
    private $type;

    /** @var array */
    private $context;

    /** @var array<string, string> [attributeName => propertyPath] */
    private $requestAttributes;

    /**
     * @param array{argument: string, type: string, context: array|null, requestAttributes: array<string, string>|null} $values
     */
    public function __construct(array $values)
    {
        if ('' === $values['argument']) {
            throw new \InvalidArgumentException('The "argument" attribute must be a non-empty string');
        }
        $this->argument = $values['argument'];

        if ('' === $values['type']) {
            throw new \InvalidArgumentException('The "type" attribute must be a non-empty string');
        }
        $this->type = $values['type'];

        $this->context = $values['context'] ?? [];

        $this->requestAttributes = $values['requestAttributes'] ?? [];
    }

    public function getArgument(): string
    {
        return $this->argument;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @return array<string, string>
     */
    public function getRequestAttributes(): array
    {
        return $this->requestAttributes;
    }
}
