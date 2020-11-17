<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\ApiDoc;

use Condenast\BasicApiBundle\Annotation\Action as ActionAnnotation;
use Doctrine\Common\Annotations\Reader;
use EXSyst\Component\Swagger\Operation;
use EXSyst\Component\Swagger\Schema;
use EXSyst\Component\Swagger\Swagger;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareInterface;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareTrait;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberInterface;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberTrait;
use Nelmio\ApiDocBundle\Util\ControllerReflector;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Routing\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ApiRouteDescriber implements RouteDescriberInterface, ModelRegistryAwareInterface
{
    use RouteDescriberTrait;
    use ModelRegistryAwareTrait;

    /** @var Reader */
    private $annotationReader;

    /** @var ControllerReflector */
    private $controllerReflector;

    public function __construct(Reader $annotationReader, ControllerReflector $controllerReflector)
    {
        $this->annotationReader = $annotationReader;
        $this->controllerReflector = $controllerReflector;
    }

    public function describe(Swagger $api, Route $route, \ReflectionMethod $reflectionMethod): void
    {
        $actionAnnotation = $this->getActionAnnotation($route);

        if (null === $actionAnnotation) {
            return;
        }

        foreach ($this->getOperations($api, $route) as $operation) {
            $this->describeOperation($operation, $actionAnnotation);
        }
    }

    private function describeOperation(Operation $operation, ActionAnnotation $actionAnnotation): void
    {
        if (null !== $actionAnnotation->getResourceName()) {
            $operation->merge(['tags' => [$actionAnnotation->getResourceName()]]);
        }

        if (empty($operation->getConsumes())) {
            $operation->merge(['consumes' => ['application/json']]);
        }

        if (empty($operation->getProduces())) {
            $operation->merge(['produces' => ['application/json']]);
        }

        $requestAnnotation = $actionAnnotation->getRequest();
        if (null !== $requestAnnotation) {
            if (!$operation->getParameters()->has('body', 'body')) {
                $body = $operation->getParameters()->get('body', 'body');

                $this->describeSchema(
                    $body->getSchema(),
                    $requestAnnotation->getType(),
                    (array)($requestAnnotation->getContext()['groups'] ?? [])
                );
            }

            if (null !== $requestAnnotation->getValidation()) {
                $badRequestResponse = $operation->getResponses()->get(HttpResponse::HTTP_BAD_REQUEST);

                if (null === $badRequestResponse->getDescription()) {
                    $badRequestResponse->setDescription(HttpResponse::$statusTexts[HttpResponse::HTTP_BAD_REQUEST]);
                }

                $this->describeSchema(
                    $badRequestResponse->getSchema(),
                    ConstraintViolationListInterface::class,
                    []
                );
            }
        }

        $responseAnnotation = $actionAnnotation->getResponse();
        if (null !== $responseAnnotation) {
            $response = $operation->getResponses()->get($responseAnnotation->getStatusCode());

            if (null === $response->getDescription()) {
                $response->setDescription(HttpResponse::$statusTexts[$responseAnnotation->getStatusCode()] ?? null);
            }

            if (null !== $responseAnnotation->getType()) {
                $this->describeSchema($response->getSchema(), $responseAnnotation->getType(), (array)($responseAnnotation->getContext()['groups'] ?? []));
            }
        }
    }

    private function describeSchema(Schema $schema, string $type, array $groups): void
    {
        if (null !== $schema->getType() || null !== $schema->getRef()) {
            return;
        }

        $collection = false;
        if ('[]' === \mb_substr($type, -2)) {
            $collection = true;
            $type = \mb_substr($type, 0, -2);
        }

        $modelRef = $this->registerModel($type, $groups);

        if ($collection) {
            $schema
                ->setType('array')
                ->getItems()->setRef($modelRef);
        } else {
            $schema->setRef($modelRef);
        }
    }

    private function registerModel(string $type, array $groups): string
    {
        return $this->modelRegistry->register(new Model(
            new Type(Type::BUILTIN_TYPE_OBJECT, false, $type),
            $groups
        ));
    }

    private function getActionAnnotation(Route $route): ?ActionAnnotation
    {
        $controller = $route->getDefault('_controller');

        if (!\is_string($controller)) {
            return null;
        }

        $methodReflection = $this->controllerReflector->getReflectionMethod($controller);

        if (null === $methodReflection) {
            return null;
        }

        /** @var ActionAnnotation $annotation */
        $annotation = $this->annotationReader->getMethodAnnotation($methodReflection, ActionAnnotation::class);

        return $annotation;
    }
}
