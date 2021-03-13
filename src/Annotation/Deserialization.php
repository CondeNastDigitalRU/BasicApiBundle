<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Annotation;

/**
 * @Annotation
 * @Target("METHOD")
 * @Attributes({
 *     @Attribute("argument", type="string", required=true),
 *     @Attribute("type", type="string", required=true),
 *     @Attribute("context", type="array")
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

    /**
     * @param array{argument: string, type: string, context: array|null} $values
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
}
