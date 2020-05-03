<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Annotation;

/**
 * @Annotation
 * @Target("ANNOTATION")
 * @Attributes({
 *     @Attribute("argument", type="string", required=true),
 *     @Attribute("type", type="string", required=true),
 *     @Attribute("context", type="array"),
 *     @Attribute("validation", type="Condenast\BasicApiBundle\Annotation\Validation")
 * })
 */
class Request
{
    /** @var string */
    private $argument;

    /** @var string */
    private $type;

    /** @var array */
    private $context;

    /** @var Validation|null */
    private $validation;

    public function __construct(array $values)
    {
        $this->argument = $values['argument'];
        $this->type = $values['type'];
        $this->context = $values['context'] ?? [];
        $this->validation = $values['validation'] ?? null;
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

    public function getValidation(): ?Validation
    {
        return $this->validation;
    }
}
