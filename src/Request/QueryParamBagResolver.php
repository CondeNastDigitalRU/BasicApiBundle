<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Request;

use Condenast\BasicApiBundle\Attribute\QueryParam;
use Condenast\BasicApiBundle\EventListener\RequestConfigurationSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class QueryParamBagResolver implements ArgumentValueResolverInterface
{
    public function __construct(private PropertyAccessorInterface $propertyAccessor, private ValidatorInterface $validator)
    {
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
        $paramFetcher = new ParamFetcher($request->query->all(), $this->propertyAccessor, $this->validator);
        $fetchedParams = [];

        /** @psalm-suppress MixedAssignment */
        foreach ($queryParams as $queryParam) {
            $fetchedParams[$queryParam->name] = $paramFetcher->get(
                $queryParam->getQueryArrayPath(),
                $queryParam->constraints,
                $queryParam->isArray,
                $queryParam->default
            );
        }

        return [new QueryParamBag($fetchedParams)];
    }
}
