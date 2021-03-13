<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Request;

use Condenast\BasicApiBundle\Annotation\QueryParam;
use Condenast\BasicApiBundle\EventListener\RequestConfigurationSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class QueryParamBagResolver implements ArgumentValueResolverInterface
{
    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return true === $request->attributes->get(RequestConfigurationSubscriber::ATTRIBUTE_API)
            && QueryParamBag::class === $argument->getType();
    }

    public function resolve(Request $request, ArgumentMetadata $argument): array
    {
        /** @var QueryParam[] $queryParams */
        $queryParams = $request->attributes->get(RequestConfigurationSubscriber::ATTRIBUTE_API_QUERY_PARAMS) ?? [];
        $paramFetcher = new ParamFetcher($request->query->all(), $this->propertyAccessor);
        $fetchedParams = [];

        foreach ($queryParams as $queryParam) {
            /** @psalm-suppress MixedAssignment */
            $fetchedParams[$queryParam->getName()] = $paramFetcher->get(
                $queryParam->getPath(),
                $queryParam->getType(),
                $queryParam->isMap()
            ) ?? $queryParam->getDefault();
        }

        return [new QueryParamBag($fetchedParams)];
    }
}
