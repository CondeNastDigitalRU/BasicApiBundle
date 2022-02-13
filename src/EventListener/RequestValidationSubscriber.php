<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\EventListener;

use Condenast\BasicApiBundle\Attribute\Deserialization;
use Condenast\BasicApiBundle\Attribute\Validation;
use Condenast\BasicApiBundle\Response\Payload;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestValidationSubscriber implements EventSubscriberInterface
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', -1040],
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        /** @var Validation|null $validation */
        $validation = $request->attributes->get(RequestConfigurationSubscriber::ATTRIBUTE_API_VALIDATION);
        /** @var Deserialization|null $deserialization */
        $deserialization = $request->attributes->get(RequestConfigurationSubscriber::ATTRIBUTE_API_DESERIALIZATION);

        if (null === $deserialization || null === $validation) {
            return;
        }

        /** @var object|array<object>|null $deserialized */
        $deserialized = $request->attributes->get($deserialization->argument);

        if (null === $deserialized) {
            return;
        }

        $violations = $this->validator->validate(
            $deserialized,
            null,
            $validation->groups
        );

        if (0 === $violations->count()) {
            return;
        }

        $event->setController(static function () use ($violations): Payload {
            return new Payload($violations, 400);
        });
    }
}
