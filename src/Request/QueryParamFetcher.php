<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Request;

use Condenast\BasicApiBundle\Exception\InvalidArgumentException;
use Condenast\BasicApiBundle\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class QueryParamFetcher
{
    public const TYPE_INT = 'int';
    public const TYPE_FLOAT = 'float';
    public const TYPE_BOOLEAN = 'bool';
    public const TYPE_STRING = 'string';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_DATETIME_IMMUTABLE = 'datetimeimmutable';

    /** @var RequestStack */
    private $requestStack;

    /** @var PropertyAccessor */
    private $propertyAccessor;

    public function __construct(RequestStack $requestStack, PropertyAccessor $propertyAccessor)
    {
        $this->requestStack = $requestStack;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @return mixed
     */
    public function get(string $name, ?string $path = null, ?string $type = null, bool $isArray = false)
    {
        if (null === $type) {
            $type = self::TYPE_STRING;
        }

        $value = $this->getRequest()->query->get($name);

        if (null !== $path) {
            if (!\is_array($value)) {
                return null;
            }

            $value = $this->propertyAccessor->getValue($value, $path);
        }

        if ($isArray) {
            if (!\is_array($value)) {
                return null;
            }

            $items = [];
            foreach ($value as $key => $item) {
                $item = $this->cast($item, $type);

                if (null === $item) {
                    return null;
                }

                $items[$key] = $item;
            }

            return $items;
        }

        return $this->cast($value, $type);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function cast($value, string $type)
    {
        if (!\is_scalar($value)) {
            return null;
        }

        switch (true) {
            case self::TYPE_BOOLEAN === $type:
                return \filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            case self::TYPE_STRING === $type:
                return (string) $value;
            case self::TYPE_INT === $type:
                return \filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            case self::TYPE_FLOAT === $type:
                return \filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
            case self::TYPE_DATETIME === $type:
            case self::TYPE_DATETIME_IMMUTABLE === $type:
                $dateTimeClass = self::TYPE_DATETIME === $type ? \DateTime::class : \DateTimeImmutable::class;
                try {
                    $value = new $dateTimeClass($value); /** @phpstan-ignore-line */
                } catch (\Exception $e) {
                    return null;
                }

                return $value;
            default:
                throw new InvalidArgumentException(\sprintf('Unknown type "%s"', $type));
        }
    }

    private function getRequest(): Request
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new RuntimeException('There is no current request');
        }

        return $request;
    }
}
