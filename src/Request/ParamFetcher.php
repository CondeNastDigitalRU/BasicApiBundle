<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Request;

use Symfony\Component\PropertyAccess\Exception\ExceptionInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ParamFetcher
{
    /**
     * @param array<array-key, mixed> $parameters
     */
    public function __construct(private array $parameters, private PropertyAccessorInterface $propertyAccessor, private ValidatorInterface $validator)
    {
    }

    /**
     * @param array<Constraint> $constraints
     */
    public function get(string $path, array $constraints = [], bool $isArray = false, mixed $default = null): mixed
    {
        try {
            /** @var mixed $value */
            $value = $this->propertyAccessor->getValue($this->parameters, $path);
        } catch (ExceptionInterface $e) {
            return $default;
        }

        if ($isArray && !\is_array($value)) {
            return $default;
        }

        try {
            $valid = empty($constraints)
                || !(bool)$this->validator->validate($value, $isArray ? new All($constraints) : $constraints)->count();
        } catch (ValidatorException $e) {
            $valid = false;
        }

        return $valid ? $value : $default;
    }
}
