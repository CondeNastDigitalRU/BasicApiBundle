<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\EventListener;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\KernelEvents;

class ErrorSubscriber implements ApiEventSubscriberInterface
{
    use ApiEventSubscriberTrait;

    /** @var ErrorListener */
    private $errorListener;

    public function __construct(ErrorListener $errorListener)
    {
        $this->errorListener = $errorListener;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', -4],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->isApiRequest($request)) {
            return;
        }

        $this->resetResponseSerializationContext($request);
        $this->errorListener->onKernelException($event);
    }
}
