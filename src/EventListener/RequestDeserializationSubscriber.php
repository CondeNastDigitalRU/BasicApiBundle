<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\EventListener;

use Condenast\BasicApiBundle\Attribute\Deserialization;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;

class RequestDeserializationSubscriber implements EventSubscriberInterface
{
    public function __construct(private SerializerInterface $serializer, private PropertyAccessorInterface $propertyAccessor)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', -1022],
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        /** @var Deserialization|null $deserialization */
        $deserialization = $request->attributes->get(RequestConfigurationSubscriber::ATTRIBUTE_API_DESERIALIZATION);

        if (null === $deserialization) {
            return;
        }

        $argument = $deserialization->argument;

        if ($request->attributes->has($argument)) {
            throw new \RuntimeException(\sprintf('An attribute with name "%s" is already present in the request', $argument));
        }

        try {
            /** @var object|array<object> $deserialized */
            $deserialized = $this->serializer->deserialize(
                $request->getContent(),
                $deserialization->type,
                'json',
                $deserialization->context
            );
        } catch (NotEncodableValueException $e) {
            throw new BadRequestHttpException('Request does not contain valid json', $e);
        } catch (ExceptionInterface|\TypeError $e) {
            throw new BadRequestHttpException('Request can\'t be deserialized', $e);
        }

        foreach ($deserialization->requestAttributes as $attribute => $propertyPath) {
            if (!$request->attributes->has($attribute)) {
                continue;
            }

            /** @var mixed $attributeValue */
            $attributeValue = $request->attributes->get($attribute);

            if (\is_array($deserialized)) {
                foreach ($deserialized as $item) {
                    $this->setRequestAttribute($item, $propertyPath, $attributeValue);
                }
            } else {
                $this->setRequestAttribute($deserialized, $propertyPath, $attributeValue);
            }
        }

        $request->attributes->set($argument, $deserialized);
    }

    private function setRequestAttribute(object $deserialized, string $propertyPath, mixed $value): void
    {
        if (!$this->propertyAccessor->isWritable($deserialized, $propertyPath)) {
            throw new \RuntimeException(\sprintf(
                'The property path "%s" is not writable in class "%s"',
                $propertyPath,
                \get_class($deserialized)
            ));
        }

        $this->propertyAccessor->setValue(
            $deserialized,
            $propertyPath,
            $value
        );
    }
}
