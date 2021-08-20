<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Annotation;

use Doctrine\Common\Annotations\Annotation\Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target("METHOD")
 * @Attributes({
 *     @Attribute("name", type="string", required=true),
 *     @Attribute("path", type="string"),
 *     @Attribute("isArray", type="bool"),
 *     @Attribute("constraints", type="array<Symfony\Component\Validator\Constraint>"),
 *     @Attribute("default", type="mixed"),
 *     @Attribute("format", type="string"),
 *     @Attribute("description", type="string")
 * })
 */
class QueryParam
{
    /** @var string */
    private $name;

    /** @var string */
    private $path;

    /** @var bool */
    private $isArray;

    /** @var array<Constraint> */
    private $constraints;

    /** @var mixed */
    private $default;

    /** @var string */
    private $description;

    /** @var string */
    private $format;

    /**
     * @param array{
     *     name: string,
     *     path: string|null,
     *     isArray: boolean,
     *     constraints: array<Constraint>,
     *     default: mixed,
     *     description: string|null,
     *     format: string|null
     * } $values
     */
    public function __construct(array $values)
    {
        if ('' === $values['name']) {
            throw new \InvalidArgumentException('The "name" attribute must be a non-empty string');
        }
        $this->name = $values['name'];

        if ('' === ($values['path'] ?? null)) {
            throw new \InvalidArgumentException('The "path" attribute must be a non-empty string or null');
        }
        $this->path = $values['path'] ?? $values['name'];

        $this->isArray = $values['isArray'] ?? false;
        $this->constraints = $values['constraints'] ?? [];
        $default = $this->isArray ? [] : null;
        $this->default = \array_key_exists('default', $values) ? $values['default'] : $default;
        $this->description = $values['description'] ?? '';
        $this->format = $values['format'] ?? '';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQueryStringParam(): string
    {
        $parts = \explode('.', $this->path);

        return \array_shift($parts)
            .(!empty($parts) ? '['.\implode('][', $parts).']' : '')
            .($this->isArray ? '[]' : '');
    }

    public function getQueryArrayPath(): string
    {
        return '['.\implode('][', \explode('.', $this->path)).']';
    }

    public function isArray(): bool
    {
        return $this->isArray;
    }

    /**
     * @return array<Constraint>
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getFormat(): string
    {
        return $this->format;
    }
}
