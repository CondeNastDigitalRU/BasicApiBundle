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
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Routing\Route;
use Symfony\Component\Validator\Constraints\Choice;
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
                $queryParam->getPath().($queryParam->isMap() ? '[]' : ''),
                'query'
            );

            if (null !== $queryParam->getDescription()) {
                Util::merge($parameter, ['description' => $queryParam->getDescription()]);
            }

            /** @var OA\Schema $schema */
            $schema = Util::getChild($parameter, OA\Schema::class);

            if (OA\UNDEFINED !== $schema->type || OA\UNDEFINED !== $schema->items || OA\UNDEFINED !== $schema->ref) {
                continue;
            }

            $item = [
                'type' => OpenApiHelper::convertParamType($queryParam->getType()),
                'format' => $queryParam->getFormat()
                    ?? OpenApiHelper::getFormatForParamType($queryParam->getType())
                    ?? OA\UNDEFINED,
                'default' => $queryParam->getDefault(),
                'enum' => $this->extractEnum($queryParam->getConstraints()),
            ];

            if ($queryParam->isMap()) {
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
     * @return mixed
     */
    private function extractEnum(array $constraints)
    {
        foreach ($constraints as $constraint) {
            if (!$constraint instanceof Choice) {
                continue;
            }

            return \is_callable($constraint->callback) ? ($constraint->callback)() : $constraint->choices;
        }

        return OA\UNDEFINED;
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
            new Type(Type::BUILTIN_TYPE_OBJECT, false, $type),
            empty($groups) ? null : $groups
        ));
    }
}
