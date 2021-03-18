<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Request;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ParamFetcher
{
    /** @var array<array-key, mixed> */
    protected $parameters;

    /** @var PropertyAccessorInterface */
    protected $propertyAccessor;

    /** @var ValidatorInterface */
    protected $validator;

    public function __construct(array $parameters, PropertyAccessorInterface $propertyAccessor, ValidatorInterface $validator)
    {
        $this->parameters = $parameters;
        $this->propertyAccessor = $propertyAccessor;
        $this->validator = $validator;
    }

    /**
     * @param list<Constraint> $constraints
     * @return mixed Parameter value or null if parameter value does not exist or does not meet requirements
     */
    public function get(string $path, string $type = ParamTypes::STRING, bool $map = false, array $constraints = [])
    {
        [$name, $nestedPath] = static::parsePath($path);

        /** @var mixed $value */
        $value = null !== $nestedPath
            ? $this->extractNested($this->parameters[$name] ?? null, $nestedPath)
            : $this->parameters[$name] ?? null;

        if ($map) {
            $value = \is_array($value) ? static::castMap($value, $type) : null;
        } else {
            /** @var mixed $value */
            $value = null !== $value ? static::cast($value, $type) : null;
        }

        $valid = true;
        if ($constraints && null !== $value) {
            $valid = !(bool) $this->validator->validate($value, $constraints)->count();
        }

        return $valid ? $value : null;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function extractNested($value, string $nestedPath)
    {
        return \is_array($value) ? $this->propertyAccessor->getValue($value, $nestedPath) : null;
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    protected static function parsePath(string $path): array
    {
        /** @var list<string> $parts */
        $parts = \explode('[', $path, 2);
        $parts[1] = 1 === \count($parts) ? null : '['.$parts[1];
        /** @var array{0: string, 1: string|null} $parts */

        return $parts;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected static function cast($value, string $type)
    {
        switch ($type) {
            case ParamTypes::MIXED:
                return $value;
            case ParamTypes::BOOLEAN:
                return \filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            case ParamTypes::STRING:
                return \is_scalar($value) ? (string) $value : null;
            case ParamTypes::INT:
                return \filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            case ParamTypes::FLOAT:
                return \filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
            case ParamTypes::DATETIME:
            case ParamTypes::DATETIME_IMMUTABLE:
                $dateTimeClass = ParamTypes::DATETIME === $type ? \DateTime::class : \DateTimeImmutable::class;

                try {
                    $value = new $dateTimeClass($value);
                } catch (\Throwable $e) {
                    return null;
                }

                return $value;
            default:
                throw new \InvalidArgumentException(\sprintf('Unknown type "%s", known types: "%s"', $type, \implode('", "', ParamTypes::TYPES)));
        }
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    protected static function castMap(array $values, string $type): ?array
    {
        $result = [];
        foreach ($values as $key => $value) {
            $value = static::cast($value, $type);

            if (null === $value && ParamTypes::MIXED !== $type) {
                return null;
            }

            $result[$key] = $value;
        }

        return $result;
    }
}
