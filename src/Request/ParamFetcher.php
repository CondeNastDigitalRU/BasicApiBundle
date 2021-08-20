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
     * @param array<Constraint> $constraints
     * @param mixed $default
     * @return mixed Parameter value or default value if parameter value does not exist or does not meet requirements
     */
    public function get(string $path, array $constraints = [], bool $isArray = false, $default = null)
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
