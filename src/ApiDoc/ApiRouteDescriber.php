<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\ApiDoc;

use Condenast\BasicApiBundle\Annotation\Deserialization;
use Condenast\BasicApiBundle\Annotation\QueryParam;
use Condenast\BasicApiBundle\Annotation\Resource;
use Condenast\BasicApiBundle\Annotation\Validation;
use Doctrine\Common\Annotations\Reader;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareInterface;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\Model\ModelRegistry;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberInterface;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberTrait;
use Nelmio\ApiDocBundle\Util\ControllerReflector;
use OpenApi\Annotations as OA;
use Symfony\Component\PropertyInfo\Type as PropertyInfoType;
use Symfony\Component\Routing\Route;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ApiRouteDescriber implements RouteDescriberInterface, ModelRegistryAwareInterface
{
    use RouteDescriberTrait;

    /** @var Reader */
    private $annotationReader;

    /** @var ControllerReflector */
    private $controllerReflector;

    /** @var ModelRegistry */
    private $modelRegistry;

    public function __construct(Reader $annotationReader, ControllerReflector $controllerReflector)
    {
        $this->annotationReader = $annotationReader;
        $this->controllerReflector = $controllerReflector;
    }

    public function setModelRegistry(ModelRegistry $modelRegistry): void
    {
        $this->modelRegistry = $modelRegistry;
    }

    public function describe(OA\OpenApi $api, Route $route, \ReflectionMethod $reflectionMethod): void
    {
        $controller = $route->getDefault('_controller');

        if (!\is_string($controller)) {
            return;
        }

        /**
         * @psalm-suppress InternalMethod
         * @var \ReflectionMethod|null
         */
        $methodReflection = $this->controllerReflector->getReflectionMethod($controller);

        if (null === $methodReflection) {
            return;
        }

        /** @var Resource|null $resource */
        $resource = $this->annotationReader->getMethodAnnotation($methodReflection, Resource::class);
        /** @var Deserialization|null $deserialization */
        $deserialization = $this->annotationReader->getMethodAnnotation($methodReflection, Deserialization::class);
        /** @var Validation|null $validation */
        $validation = $this->annotationReader->getMethodAnnotation($methodReflection, Validation::class);
        /** @var list<QueryParam> $queryParameters */
        $queryParameters = \array_filter(
            $this->annotationReader->getMethodAnnotations($methodReflection),
            static function (object $annotation): bool {
                return $annotation instanceof QueryParam;
            }
        );

        /** @psalm-suppress InternalMethod */
        foreach ($this->getOperations($api, $route) as $operation) {
            null !== $resource && $this->describeOperation($operation, $resource);
            null !== $deserialization && $this->describeRequestBody($operation, $deserialization);
            null !== $deserialization && null !== $validation && $this->describeValidationResponse($operation);
            $this->describeQueryParams($operation, $queryParameters);
        }
    }

    private function describeOperation(OA\Operation $operation, Resource $resource): void
    {
        Util::merge(
            $operation,
            [
                'tags' => [$resource->getName()],
                'operationId' => \sprintf('%s %s', $operation->method, $operation->path),
            ]
        );
    }

    private function describeRequestBody(OA\Operation $operation, Deserialization $deserialization): void
    {
        /** @var list<string> $groups */
        $groups = (array) ($deserialization->getContext()['groups'] ?? []);

        /** @psalm-suppress ArgumentTypeCoercion */
        $this->describeMediaType(
            Util::getChild($operation, OA\RequestBody::class),
            $deserialization->getType(),
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
     * @param list<QueryParam> $queryParams
     */
    private function describeQueryParams(OA\Operation $operation, array $queryParams): void
    {
        foreach ($queryParams as $queryParam) {
            $parameter = Util::getOperationParameter(
                $operation,
                $queryParam->getQueryStringParam(),
                'query'
            );

            Util::merge($parameter, ['description' => $this->extractDescription($queryParam) ?? OA\UNDEFINED]);

            /** @var OA\Schema $schema */
            $schema = Util::getChild($parameter, OA\Schema::class);

            if (OA\UNDEFINED !== $schema->type || OA\UNDEFINED !== $schema->items || OA\UNDEFINED !== $schema->ref) {
                continue;
            }

            $item = [
                'type' => 'string',
                'format' => $this->extractFormat($queryParam) ?? OA\UNDEFINED,
                'default' => $queryParam->getDefault(),
                'enum' => $this->extractEnum($queryParam->getConstraints()) ?? OA\UNDEFINED,
                'pattern' => $this->extractPattern($queryParam->getConstraints()) ?? OA\UNDEFINED,
            ];

            if ($queryParam->isArray()) {
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
     * @param OA\RequestBody|OA\Response $body
     * @param list<string> $groups
     */
    private function describeMediaType($body, string $type, array $groups = []): void
    {
        /** @var OA\MediaType $mediaType */
        $mediaType = Util::getIndexedCollectionItem($body, OA\MediaType::class, 'application/json');
        /** @var OA\Schema $schema */
        $schema = Util::getChild($mediaType, OA\Schema::class);

        if (OA\UNDEFINED !== $schema->type || OA\UNDEFINED !== $schema->items || OA\UNDEFINED !== $schema->ref) {
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
     * @param list<string> $groups
     */
    private function registerModel(string $type, array $groups = []): string
    {
        return $this->modelRegistry->register(new Model(
            new PropertyInfoType(PropertyInfoType::BUILTIN_TYPE_OBJECT, false, $type),
            empty($groups) ? null : $groups
        ));
    }

    /**
     * @param list<Constraint> $constraints
     */
    private function extractEnum(array $constraints): ?array
    {
        $constraint = $this->findConstraint($constraints, Constraints\Choice::class);

        if (null === $constraint) {
            return null;
        }

        /** @var list<string> $enum */
        $enum = \is_callable($constraint->callback) ? ($constraint->callback)() : $constraint->choices;

        return $enum;
    }

    /**
     * @param list<Constraint> $constraints
     */
    private function extractPattern(array $constraints): ?string
    {
        $constraint = $this->findConstraint($constraints, Constraints\Regex::class);

        return null !== $constraint ? $constraint->getHtmlPattern() : null;
    }

    private function extractDescription(QueryParam $queryParam): ?string
    {
        $description = [];

        if ('' !== $queryParam->getDescription()) {
            $description[] = $queryParam->getDescription();
        }

        $requirements = \array_map(
            static function (string $desc): string {
                return '* '.$desc;
            },
            $this->extractRequirements($queryParam->getConstraints())
        );

        if (!empty($requirements)) {
            $description = \array_merge($description, ['*Requirements*'], $requirements);
        }

        return !empty($description) ? \implode("\n\n", $description) : null;
    }

    /**
     * @param list<Constraint> $constraints
     * @return list<string>
     */
    private function extractRequirements(array $constraints): array
    {
        $requirements = [];

        foreach ($constraints as $constraint) {
            $title = Helper::camelCaseToSentence((new \ReflectionClass($constraint))->getShortName());
            switch (true) {
                case $constraint instanceof Constraints\AbstractComparison:
                    $requirements[] = \sprintf(
                        '%s: %s',
                        $title,
                        Helper::toString($constraint->value)
                    );
                    break;
                case $constraint instanceof Constraints\Type:
                    $requirements[] = \sprintf(
                        '%s: %s',
                        $title,
                        Helper::toString($constraint->type)
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
        if ('' !== $queryParam->getFormat()) {
            return $queryParam->getFormat();
        }

        $format = null;

        foreach ($queryParam->getConstraints() as $constraint) {
            $title = Helper::camelCaseToSentence((new \ReflectionClass($constraint))->getShortName());

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
     * @param list<Constraint> $constraints
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
}
