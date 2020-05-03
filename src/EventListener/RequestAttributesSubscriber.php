<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\EventListener;

use Condenast\BasicApiBundle\Annotation\Action;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestAttributesSubscriber implements ApiEventSubscriberInterface
{
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

        if (!\is_array($controller) || \count($controller) !== 2) {
            return;
        }

        [$class, $method] = $controller;

        try {
            $controllerReflection = new \ReflectionClass($class);
        } catch (\ReflectionException $e) {
            return;
        }

        /** @var Action|null $actionAnnotation */
        $actionAnnotation = $this->annotationsReader->getMethodAnnotation($controllerReflection->getMethod($method), Action::class);

        if (null === $actionAnnotation) {
            return;
        }

        $request = $event->getRequest();
        $request->attributes->set(self::ATTRIBUTE_API, true);

        if ($requestAnnotation = $actionAnnotation->getRequest()) {
            $request->attributes->set(self::ATTRIBUTE_DESERIALIZE, true);
            $request->attributes->set(self::ATTRIBUTE_DESERIALIZATION_TYPE, $requestAnnotation->getType());
            $request->attributes->set(self::ATTRIBUTE_DESERIALIZATION_CONTEXT, $requestAnnotation->getContext());
            $request->attributes->set(self::ATTRIBUTE_CONTROLLER_ARGUMENT, $requestAnnotation->getArgument());

            if ($validationAnnotation = $requestAnnotation->getValidation()) {
                $request->attributes->set(self::ATTRIBUTE_VALIDATE, true);
                $request->attributes->set(self::ATTRIBUTE_VALIDATION_GROUPS, $validationAnnotation->getGroups());
            }
        }

        if ($responseAnnotation = $actionAnnotation->getResponse()) {
            $request->attributes->set(self::ATTRIBUTE_SERIALIZATION_TYPE, $responseAnnotation->getType());
            $request->attributes->set(self::ATTRIBUTE_SERIALIZATION_CONTEXT, $responseAnnotation->getContext());
            $request->attributes->set(self::ATTRIBUTE_STATUS_CODE, $responseAnnotation->getStatusCode());
        }
    }
}
