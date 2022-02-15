<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Attribute;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_METHOD|\Attribute::IS_REPEATABLE)]
class QueryParam
{
    public readonly string $path;
    public readonly mixed $default;

    /**
     * @param array<Constraint> $constraints
     */
    public function __construct(
        public readonly string $name,
        ?string $path = null,
        public readonly bool $isArray = false,
        public readonly array $constraints = [],
        mixed $default = null,
        public readonly string $description = '',
        public readonly string $format = '',
    ) {
        '' === $name && throw new \InvalidArgumentException('The "name" must be a non-empty string');
        '' === $path && throw new \InvalidArgumentException('The "path" must be a non-empty string or null');

        $this->path = $path ?? $this->name;
        $this->default = $default ?? ($this->isArray ? [] : null);
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
}
