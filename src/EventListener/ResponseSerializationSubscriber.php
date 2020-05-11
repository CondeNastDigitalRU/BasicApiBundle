<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\EventListener;

use Condenast\BasicApiBundle\Response\ApiResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

class ResponseSerializationSubscriber implements ApiEventSubscriberInterface
{
    use ApiEventSubscriberTrait;

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
            KernelEvents::RESPONSE => ['onKernelResponse', 4],
        ];
    }

    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->isApiRequest($request)) {
            return;
        }

        $event->setResponse(new ApiResponse($event->getControllerResult()));
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (!$response instanceof ApiResponse || !$this->isApiRequest($request)) {
            return;
        }

        if (!$response->isStatusCodeSet()) {
            $response->setStatusCode($this->getResponseStatusCode($request));
        }

        if (null === $response->getData()) {
            return;
        }

        $response->setContent($this->serializer->serialize(
            $response->getData(),
            'json',
            $this->getResponseSerializationContext($request)
        ));

        if (!$response->headers->has('Content-Type')) {
            $response->headers->set('Content-Type', 'application/json');
        }
    }
}
