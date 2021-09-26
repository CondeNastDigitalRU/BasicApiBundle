<?php
declare(strict_types=1);

namespace Condenast\BasicApiBundle\Annotation;

use Doctrine\Common\Annotations\Annotation\Attribute;
use Symfony\Component\Validator\Constraint;
use Webmozart\Assert\Assert;

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
#[\Attribute(\Attribute::TARGET_METHOD)]
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
     *     } $data
     */
    public function __construct(
        array $data = [],
        string $name = '',
        ?string $path = null,
        bool $isArray = false,
        array $constraints = [],
        $default = null,
        ?string $description = null,
        ?string $format = null
    ) {
        $deprecation = false;
        foreach ($data as $key => $val) {
            if (\in_array($key, ['values', 'name', 'path', 'isArray', 'constraints', 'default', 'description', 'format']
            )) {
                $deprecation = true;
            }
        }

        if ($deprecation) {
            trigger_deprecation(
                'Condenast\BasicApiBundle',
                '2.1',
                'Passing an array as first argument to "%s" is deprecated. Use named arguments instead.',
                __METHOD__
            );
        }

        $data['name'] = $data['name'] ?? $name;
        $data['path'] = $data['path'] ?? $path;
        $data['isArray'] = $data['isArray'] ?? $isArray;
        $data['constraints'] = $data['constraints'] ?? $constraints;
        $data['default'] = $data['default'] ?? $default;
        $data['description'] = $data['description'] ?? $description;
        $data['format'] = $data['format'] ?? $format;

        Assert::allIsInstanceOf($data['constraints'], Constraint::class);

        if ('' === $data['name']) {
            throw new \InvalidArgumentException('The "name" attribute must be a non-empty string');
        }
        $this->name = $data['name'];

        if ('' === ($data['path'] ?? null)) {
            throw new \InvalidArgumentException('The "path" attribute must be a non-empty string or null');
        }
        $this->path = $data['path'] ?? $data['name'];

        $this->isArray = $data['isArray'] ?? false;
        $this->constraints = $data['constraints'] ?? [];
        $default = $this->isArray ? [] : null;
        $this->default = \array_key_exists('default', $data) ? $data['default'] : $default;
        $this->description = $data['description'] ?? '';
        $this->format = $data['format'] ?? '';
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
