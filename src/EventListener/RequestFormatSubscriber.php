<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestFormatSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 1022],
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $contentType = $request->getContentType();

        if ('json' === $contentType
            || null === $request->attributes->get(RequestConfigurationSubscriber::ATTRIBUTE_API_DESERIALIZATION)
        ) {
            return;
        }

        throw new UnsupportedMediaTypeHttpException(\sprintf(
            'Unexpected request format "%s", expected request format is "json". Consider adding a json body and a Content-Type header to your request, such as "application/json"',
            $contentType ?? 'unknown'
        ));
    }
}
