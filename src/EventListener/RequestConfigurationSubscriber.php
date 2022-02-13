<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\EventListener;

use Condenast\BasicApiBundle\Attribute\Deserialization;
use Condenast\BasicApiBundle\Attribute\QueryParam;
use Condenast\BasicApiBundle\Attribute\Resource;
use Condenast\BasicApiBundle\Attribute\Validation;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestConfigurationSubscriber implements EventSubscriberInterface
{
    public const ATTRIBUTE_API = '_basic_api';
    public const ATTRIBUTE_API_RESOURCE = '_basic_api_resource';
    public const ATTRIBUTE_API_DESERIALIZATION = '_basic_api_deserialization';
    public const ATTRIBUTE_API_VALIDATION = '_basic_api_validation';
    public const ATTRIBUTE_API_QUERY_PARAMS = '_basic_api_query_params';

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 1024],
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (\is_object($controller) && \method_exists($controller, '__invoke')) {
            $controller = [$controller, '__invoke'];
        }

        if (!\is_array($controller)) {
            return;
        }

        [$class, $method] = $controller;

        try {
            $controllerReflection = new \ReflectionClass($class);
            $methodReflection = $controllerReflection->getMethod($method);
        } catch (\ReflectionException $e) {
            return;
        }

        $request = $event->getRequest();

        $resource = ($methodReflection->getAttributes(Resource::class)[0] ?? null)?->newInstance();
        null !== $resource && $request->attributes->set(self::ATTRIBUTE_API_RESOURCE, $resource);

        $deserialization = ($methodReflection->getAttributes(Deserialization::class)[0] ?? null)?->newInstance();
        null !== $deserialization && $request->attributes->set(self::ATTRIBUTE_API_DESERIALIZATION, $deserialization);

        $validation = ($methodReflection->getAttributes(Validation::class)[0] ?? null)?->newInstance();
        null !== $validation && $request->attributes->set(self::ATTRIBUTE_API_VALIDATION, $validation);

        $queryParams = [];
        foreach (\array_map(static fn (\ReflectionAttribute $attribute) => $attribute->newInstance(), $methodReflection->getAttributes(QueryParam::class)) as $queryParam) {
            if (\array_key_exists($queryParam->name, $queryParams)) {
                throw new \RuntimeException(\sprintf(
                    'The query parameter name "%s" is already used, consider renaming the parameter name in the QueryParam annotation',
                    $queryParam->name
                ));
            }
            $queryParams[$queryParam->name] = $queryParam;
        }

        if (!empty($queryParams)) {
            $request->attributes->set(self::ATTRIBUTE_API_QUERY_PARAMS, $queryParams);
        }

        if (empty(\array_filter([$resource, $deserialization, $validation, $queryParams]))) {
            return;
        }

        $request->setRequestFormat('json');
        $request->attributes->set(self::ATTRIBUTE_API, true);
    }
}
