<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\EventListener;

use Condenast\BasicApiBundle\Request\RequestHelper;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class FormatSubscriber implements ApiEventSubscriberInterface
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

        if (
            !RequestHelper::isApiRequest($request)
            || !RequestHelper::canRequestHaveBody($request)
            || $request->getContent() === ''
            || RequestHelper::isJsonContentTypeRequest($request)
        ) {
            return;
        }

        throw new UnsupportedMediaTypeHttpException(\sprintf(
            'Unexpected request format "%s", expected request format is "json"',
            $request->getContentType()
        ));
    }
}
