<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Annotation;

use Condenast\BasicApiBundle\Request\ParamTypes;
use Doctrine\Common\Annotations\Annotation\Attribute;

/**
 * @Annotation
 * @Target("METHOD")
 * @Attributes({
 *     @Attribute("name", type="string", required=true),
 *     @Attribute("path", type="string"),
 *     @Attribute("type", type="string", required=true),
 *     @Attribute("default", type="mixed"),
 *     @Attribute("format", type="string"),
 *     @Attribute("map", type="bool"),
 *     @Attribute("description", type="string")
 * })
 */
class QueryParam
{
    /** @var string */
    private $name;

    /** @var string */
    private $path;

    /** @var string */
    private $type;

    /** @var bool */
    private $map;

    /** @var mixed */
    private $default;

    /** @var string|null */
    private $description;

    /** @var string|null */
    private $format;

    /**
     * @param array{
     *     name: string,
     *     path: string|null,
     *     type: string,
     *     map: boolean,
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

        if (!\in_array($values['type'], ParamTypes::TYPES, true)) {
            throw new \InvalidArgumentException(\sprintf(
                'Unknown type "%s", known types: "%s"',
                $values['type'],
                \implode('", "', ParamTypes::TYPES)
            ));
        }
        $this->type = $values['type'];

        $this->map = $values['map'] ?? false;
        $this->default = $values['default'] ?? null;
        $this->description = $values['description'] ?? null;
        $this->format = $values['format'] ?? null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isMap(): bool
    {
        return $this->map;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default ?? ($this->isMap() ? [] : null);
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }
}
