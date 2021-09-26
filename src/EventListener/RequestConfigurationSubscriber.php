<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\EventListener;

use Condenast\BasicApiBundle\Annotation\Deserialization;
use Condenast\BasicApiBundle\Annotation\QueryParam;
use Condenast\BasicApiBundle\Annotation\Resource;
use Condenast\BasicApiBundle\Annotation\Validation;
use Doctrine\Common\Annotations\Reader;
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

    /** @var Reader */
    private $annotationsReader;

    public function __construct(Reader $annotationsReader)
    {
        $this->annotationsReader = $annotationsReader;
    }

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

        $resource = $this->getAnnotation($methodReflection, Resource::class);
        null !== $resource && $request->attributes->set(self::ATTRIBUTE_API_RESOURCE, $resource);

        $deserialization = $this->getAnnotation($methodReflection, Deserialization::class);
        null !== $deserialization && $request->attributes->set(self::ATTRIBUTE_API_DESERIALIZATION, $deserialization);

        $validation = $this->getAnnotation($methodReflection, Validation::class);
        null !== $validation && $request->attributes->set(self::ATTRIBUTE_API_VALIDATION, $validation);

        $queryParams = [];
        foreach ($this->annotationsReader->getMethodAnnotations($methodReflection) as $queryParam) {
            if ($queryParam instanceof QueryParam) {
                if (\array_key_exists($queryParam->getName(), $queryParams)) {
                    throw new \RuntimeException(\sprintf(
                        'The query parameter name "%s" is already used, consider renaming the parameter name in the QueryParam annotation',
                        $queryParam->getName()
                    ));
                }

                $queryParams[$queryParam->getName()] = $queryParam;
            }
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

	private function getAnnotation(\ReflectionMethod $reflection, string $annotationClass)
	{
		if (\PHP_VERSION_ID >= 80000) {
			foreach ($reflection->getAttributes($annotationClass) as $attribute) {
				return $attribute->newInstance();
			}
		}

		return $this->annotationsReader->getMethodAnnotation($reflection, $annotationClass);
	}
}
