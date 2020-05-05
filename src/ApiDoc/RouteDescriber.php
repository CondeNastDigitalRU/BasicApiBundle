<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\ApiDoc;

use Condenast\BasicApiBundle\Annotation\Action;
use Condenast\BasicApiBundle\Annotation\Request;
use Condenast\BasicApiBundle\Annotation\Response;
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

final class RouteDescriber implements RouteDescriberInterface, ModelRegistryAwareInterface
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
        foreach ($this->getOperations($api, $route) as $operation) {
            if (null === ($actionAnnotation = $this->getActionAnnotation($route))) {
                continue;
            }

            if ($actionAnnotation->getResourceName() !== null) {
                $operation->merge(['tags' => [$actionAnnotation->getResourceName()]]);
            }

            $operation->merge([
                'consumes' => ['application/json'],
                'produces' => ['application/json']
            ]);

            if (null !== ($requestAnnotation = $actionAnnotation->getRequest())) {
                $this->describeRequest($operation, $requestAnnotation);
            }

            if (null !== ($responseAnnotation = $actionAnnotation->getResponse())) {
                $this->describeResponse($operation, $responseAnnotation);
            }
        }
    }

    private function describeRequest(Operation $operation, Request $requestAnnotation): void
    {
        $this->describeModel(
            $operation->getParameters()->get('body', 'body')->getSchema(),
            $requestAnnotation->getType(),
            (array) ($requestAnnotation->getContext()['groups'] ?? [])
        );

        if ($requestAnnotation->getValidation() !== null) {
            $this->describeModel(
                $operation->getResponses()->get(HttpResponse::HTTP_BAD_REQUEST)->setDescription('Bad request')->getSchema(),
                ConstraintViolationListInterface::class,
                []
            );
        }
    }

    private function describeResponse(Operation $operation, Response $responseAnnotation): void
    {
        $response = $operation
            ->getResponses()
            ->get($responseAnnotation->getStatusCode())
            ->setDescription(HttpResponse::$statusTexts[$responseAnnotation->getStatusCode()] ?? '')
        ;

        if (null === $responseAnnotation->getType()) {
            return;
        }

        $this->describeModel(
            $response->getSchema(),
            $responseAnnotation->getType(),
            (array) ($responseAnnotation->getContext()['groups'] ?? [])
        );
    }

    private function describeModel(Schema $schema, string $type, array $groups): void
    {
        $collection = false;
        if (\substr($type, -2) === '[]') {
            $collection = true;
            $type = \substr($type, 0, -2);
        }

        $model = $this->modelRegistry->register(new Model(
            new Type(Type::BUILTIN_TYPE_OBJECT, false, $type),
            $groups
        ));

        if ($collection) {
            $schema->setType('array')->getItems()->setRef($model);
        } else {
            $schema->setRef($model);
        }
    }

    private function getActionAnnotation(Route $route): ?Action
    {
        $methodReflection = $this->controllerReflector->getReflectionMethod($route->getDefault('_controller') ?? '');

        if (null === $methodReflection) {
            return null;
        }

        return $this->annotationReader->getMethodAnnotation($methodReflection, Action::class); /** @phpstan-ignore-line */
    }
}
