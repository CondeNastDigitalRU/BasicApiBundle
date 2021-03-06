<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\EventListener;

use Condenast\BasicApiBundle\Annotation\Deserialization;
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
    /** @var SerializerInterface */
    private $serializer;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    public function __construct(SerializerInterface $serializer, PropertyAccessorInterface $propertyAccessor)
    {
        $this->serializer = $serializer;
        $this->propertyAccessor = $propertyAccessor;
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

        $argument = $deserialization->getArgument();

        if ($request->attributes->has($argument)) {
            throw new \RuntimeException(\sprintf('An attribute with name "%s" is already present in the request', $argument));
        }

        try {
            /** @var object|array<object> $deserialized */
            $deserialized = $this->serializer->deserialize(
                $request->getContent(),
                $deserialization->getType(),
                'json',
                $deserialization->getContext()
            );
        } catch (NotEncodableValueException $e) {
            throw new BadRequestHttpException('Request does not contain valid json', $e);
        } catch (ExceptionInterface|\TypeError $e) {
            throw new BadRequestHttpException('Request can\'t be deserialized', $e);
        }

        foreach ($deserialization->getRequestAttributes() as $attribute => $propertyPath) {
            if (!$request->attributes->has($attribute)) {
                continue;
            }

            /** @var mixed $attributeValue */
            $attributeValue = $request->get($attribute);

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

    /**
     * @param mixed $value
     */
    private function setRequestAttribute(object $deserialized, string $propertyPath, $value): void
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
