<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\EventListener;

use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;

class RequestDeserializationSubscriber implements ApiEventSubscriberInterface
{
    use ApiEventSubscriberTrait;

    /** @var SerializerInterface */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
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

        /** @var string $content */
        $content = $request->getContent();

        if (
            '' === $content
            || !$this->isApiRequest($request)
            || !$this->isRequestDeserializationEnabled($request)
            || !$this->canRequestHaveBody($request)
        ) {
            return;
        }

        try {
            $this->setControllerArgument(
                $request,
                $this->serializer->deserialize(
                    $content,
                    $this->getRequestDeserializationType($request),
                    'json',
                    $this->getRequestDeserializationContext($request)
                )
            );
        } catch (NotEncodableValueException $e) {
            throw new BadRequestHttpException('Request does not contain valid json', $e);
        } catch (ExceptionInterface $e) {
            throw new BadRequestHttpException('Request can\'t be deserialized', $e);
        }
    }
}
