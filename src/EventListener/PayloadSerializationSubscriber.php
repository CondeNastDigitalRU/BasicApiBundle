<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\EventListener;

use Condenast\BasicApiBundle\Response\Payload;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

class PayloadSerializationSubscriber implements EventSubscriberInterface
{
    /** @var SerializerInterface */
    protected $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onKernelView', 4],
        ];
    }

    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();
        $payload = $event->getControllerResult();

        if (
            !$payload instanceof Payload
            || true !== $request->attributes->get(RequestConfigurationSubscriber::ATTRIBUTE_API)
        ) {
            return;
        }

        /** @var mixed $data */
        $data = $payload->getData();

        $json = null !== $data
            ? $this->serializer->serialize($data, 'json', $payload->getSerializationContext())
            : '';

        $response = new Response(
            $json,
            $payload->getStatus(),
            $payload->getHeaders()
        );

        if ('' !== $json && !$response->headers->has('Content-Type')) {
            $response->headers->set('Content-Type', 'application/json');
        }

        $event->setResponse($response);
    }
}
