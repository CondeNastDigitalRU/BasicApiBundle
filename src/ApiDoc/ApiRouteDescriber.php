<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\ApiDoc;

use Condenast\BasicApiBundle\Attribute\Deserialization;
use Condenast\BasicApiBundle\Attribute\QueryParam;
use Condenast\BasicApiBundle\Attribute\Resource;
use Condenast\BasicApiBundle\Attribute\Validation;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareInterface;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\Model\ModelRegistry;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberInterface;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberTrait;
use OpenApi\Annotations as OA;
use OpenApi\Generator;
use Symfony\Component\PropertyInfo\Type as PropertyInfoType;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ApiRouteDescriber implements RouteDescriberInterface, ModelRegistryAwareInterface
{
    use RouteDescriberTrait;

    private ModelRegistry $modelRegistry;

    /**
     * @param array<RouteCollection> $routeCollections
     */
    public function __construct(private readonly array $routeCollections)
    {
    }

    public function setModelRegistry(ModelRegistry $modelRegistry): void
    {
        $this->modelRegistry = $modelRegistry;
    }

    public function describe(OA\OpenApi $api, Route $route, \ReflectionMethod $reflectionMethod): void
    {
        $resource = ($reflectionMethod->getAttributes(Resource::class)[0] ?? null)?->newInstance();
        $deserialization = ($reflectionMethod->getAttributes(Deserialization::class)[0] ?? null)?->newInstance();
        $validation = ($reflectionMethod->getAttributes(Validation::class)[0] ?? null)?->newInstance();

        $queryParameters = \array_map(
            static fn (\ReflectionAttribute $attr) => $attr->newInstance(),
            $reflectionMethod->getAttributes(QueryParam::class)
        );

        /** @psalm-suppress InternalMethod */
        foreach ($this->getOperations($api, $route) as $operation) {
            null !== $resource && $this->describeOperation($route, $operation, $resource);
            null !== $deserialization && $this->describeRequestBody($operation, $deserialization);
            null !== $deserialization && null !== $validation && $this->describeValidationResponse($operation);
            $this->describeQueryParams($operation, $queryParameters);
        }
    }

    private function describeOperation(Route $route, OA\Operation $operation, Resource $resource): void
    {
        Util::merge(
            $operation,
            [
                'tags' => [$resource->name],
                'operationId' => StringHelper::slugify($this->getRouteName($route).'-'.$operation->method),
            ]
        );
    }

    private function describeRequestBody(OA\Operation $operation, Deserialization $deserialization): void
    {
        /** @var array<string> $groups */
        $groups = (array) ($deserialization->context['groups'] ?? []);

        /** @psalm-suppress ArgumentTypeCoercion */
        $this->describeMediaType(
            Util::getChild($operation, OA\RequestBody::class),
            $deserialization->type,
            $groups
        );
    }

    private function describeValidationResponse(OA\Operation $operation): void
    {
        /** @var OA\Response $response */
        $response = Util::getIndexedCollectionItem($operation, OA\Response::class, 400);
        Util::merge($response, ['description' => 'Bad request']);

        $this->describeMediaType(
            $response,
            ConstraintViolationListInterface::class
        );
    }

    /**
     * @param array<QueryParam> $queryParams
     */
    private function describeQueryParams(OA\Operation $operation, array $queryParams): void
    {
        foreach ($queryParams as $queryParam) {
            $parameter = Util::getOperationParameter(
                $operation,
                $queryParam->getQueryStringParam(),
                'query'
            );

            Util::merge($parameter, ['description' => $this->extractDescription($queryParam) ?? Generator::UNDEFINED]);

            /** @var OA\Schema $schema */
            $schema = Util::getChild($parameter, OA\Schema::class);

            /**
             * @psalm-suppress RedundantConditionGivenDocblockType, DocblockTypeContradiction
             */
            if (Generator::UNDEFINED !== $schema->type || Generator::UNDEFINED !== $schema->items || Generator::UNDEFINED !== $schema->ref) {
                continue;
            }

            $item = [
                'type' => 'string',
                'format' => $this->extractFormat($queryParam) ?? Generator::UNDEFINED,
                'default' => $queryParam->default,
                'enum' => $this->extractEnum($queryParam->constraints) ?? Generator::UNDEFINED,
                'pattern' => $this->extractPattern($queryParam->constraints) ?? Generator::UNDEFINED,
            ];

            if ($queryParam->isArray) {
                $properties = [
                    'type' => 'array',
                    'items' => new OA\Items($item),
                ];
            } else {
                $properties = $item;
            }

            Util::merge($schema, $properties);
        }
    }

    /**
     * @param array<string> $groups
     */
    private function describeMediaType(OA\RequestBody|OA\Response $body, string $type, array $groups = []): void
    {
        /** @var OA\MediaType $mediaType */
        $mediaType = Util::getIndexedCollectionItem($body, OA\MediaType::class, 'application/json');
        /** @var OA\Schema $schema */
        $schema = Util::getChild($mediaType, OA\Schema::class);

        /**
         * @psalm-suppress RedundantConditionGivenDocblockType, DocblockTypeContradiction
         */
        if (Generator::UNDEFINED !== $schema->type || Generator::UNDEFINED !== $schema->items || Generator::UNDEFINED !== $schema->ref) {
            return;
        }

        $collection = false;
        if ('[]' === \mb_substr($type, -2)) {
            $collection = true;
            $type = \mb_substr($type, 0, -2);
        }

        $modelRef = $this->registerModel($type, $groups);

        if ($collection) {
            $properties = [
                'type' => 'array',
                'items' => new OA\Items(['ref' => $modelRef]),
            ];
        } else {
            $properties = [
                'type' => 'object',
                'ref' => $modelRef,
            ];
        }

        Util::merge($schema, $properties);
    }

    /**
     * @param array<string> $groups
     */
    private function registerModel(string $type, array $groups = []): string
    {
        return $this->modelRegistry->register(new Model(
            new PropertyInfoType(PropertyInfoType::BUILTIN_TYPE_OBJECT, false, $type),
            empty($groups) ? null : $groups
        ));
    }

    /**
     * @param array<Constraint> $constraints
     */
    private function extractEnum(array $constraints): ?array
    {
        $constraint = $this->findConstraint($constraints, Constraints\Choice::class);

        if (null === $constraint) {
            return null;
        }

        /** @var array<string> $enum */
        $enum = \is_callable($constraint->callback) ? ($constraint->callback)() : $constraint->choices;

        return $enum;
    }

    /**
     * @param array<Constraint> $constraints
     */
    private function extractPattern(array $constraints): ?string
    {
        $constraint = $this->findConstraint($constraints, Constraints\Regex::class);

        return null !== $constraint ? $constraint->getHtmlPattern() : null;
    }

    private function extractDescription(QueryParam $queryParam): ?string
    {
        $description = [];

        if ('' !== $queryParam->description) {
            $description[] = $queryParam->description;
        }

        $requirements = \array_map(
            static function (string $desc): string {
                return '* '.$desc;
            },
            $this->extractRequirements($queryParam->constraints)
        );

        if (!empty($requirements)) {
            $description = \array_merge($description, ['*Requirements*'], $requirements);
        }

        return !empty($description) ? \implode("\n\n", $description) : null;
    }

    /**
     * @param array<Constraint> $constraints
     * @return array<string>
     */
    private function extractRequirements(array $constraints): array
    {
        $requirements = [];

        foreach ($constraints as $constraint) {
            $title = StringHelper::toSentence((new \ReflectionClass($constraint))->getShortName());
            switch (true) {
                case $constraint instanceof Constraints\AbstractComparison:
                    $requirements[] = \sprintf(
                        '%s: %s',
                        $title,
                        StringHelper::toString($constraint->value)
                    );
                    break;
                case $constraint instanceof Constraints\Type:
                    $requirements[] = \sprintf(
                        '%s: %s',
                        $title,
                        StringHelper::toString($constraint->type)
                    );
                    break;
                case $constraint instanceof Constraints\Length:
                    $requirements[] = \sprintf(
                        '%s: %s',
                        $title,
                        \implode(', ', \array_filter([
                            null !== $constraint->min ? \sprintf('min - %s', (string) $constraint->min) : '',
                            null !== $constraint->max ? \sprintf('max - %s', (string) $constraint->max) : '',
                        ]))
                    );
                    break;
                case $constraint instanceof Constraints\Regex:
                case $constraint instanceof Constraints\Choice:
                    break;
                default:
                    $requirements[] = $title;
            }
        }

        return $requirements;
    }

    public function extractFormat(QueryParam $queryParam): ?string
    {
        if ('' !== $queryParam->format) {
            return $queryParam->format;
        }

        $format = null;

        foreach ($queryParam->constraints as $constraint) {
            $title = StringHelper::toSentence((new \ReflectionClass($constraint))->getShortName());

            switch (true) {
                case $constraint instanceof Constraints\Bic:
                case $constraint instanceof Constraints\CardScheme:
                case $constraint instanceof Constraints\Country:
                case $constraint instanceof Constraints\Currency:
                case $constraint instanceof Constraints\Date:
                case $constraint instanceof Constraints\DateTime:
                case $constraint instanceof Constraints\Email:
                case $constraint instanceof Constraints\Hostname:
                case $constraint instanceof Constraints\Iban:
                case $constraint instanceof Constraints\Isbn:
                case $constraint instanceof Constraints\Isin:
                case $constraint instanceof Constraints\Issn:
                case $constraint instanceof Constraints\Language:
                case $constraint instanceof Constraints\Locale:
                case $constraint instanceof Constraints\Luhn:
                case $constraint instanceof Constraints\Time:
                case $constraint instanceof Constraints\Timezone:
                case $constraint instanceof Constraints\Ulid:
                case $constraint instanceof Constraints\Url:
                case $constraint instanceof Constraints\Uuid:
                    $format = $title;
                    break 2;
            }
        }

        return $format;
    }

    /**
     * @template T of Constraint
     * @param array<Constraint> $constraints
     * @param class-string<T> $class
     * @return T|null
     */
    private function findConstraint(array $constraints, string $class): ?Constraint
    {
        return \current(\array_filter(
            $constraints,
            static function (Constraint $constraint) use ($class): bool {
                return \is_a($constraint, $class);
            }
        )) ?: null;
    }

    private function getRouteName(Route $route): string
    {
        foreach ($this->routeCollections as $routeCollection) {
            /**
             * @var int|string $name
             */
            foreach ($routeCollection->all() as $name => $collectionRoute) {
                if ($collectionRoute === $route) {
                    return (string) $name;
                }
            }
        }

        throw new \InvalidArgumentException('Unable to found route name');
    }
}
