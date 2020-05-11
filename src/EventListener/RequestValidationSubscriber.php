<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\EventListener;

use Condenast\BasicApiBundle\Response\ApiResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestValidationSubscriber implements ApiEventSubscriberInterface
{
    use ApiEventSubscriberTrait;
    
    /** @var ValidatorInterface */
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', -1024],
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        if (
            !$this->isApiRequest($request)
            || !$this->isRequestValidationEnabled($request)
            || null === $deserialized = $this->getControllerArgument($request)
        ) {
            return;
        }

        $groups = $this->getRequestValidationGroups($request);

        if ($this->isRequestValidationGroupSequence($request)) {
            $groups = new GroupSequence($groups);
        }

        $violations = $this->validator->validate(
            $deserialized,
            null,
            $groups
        );

        if (0 !== $violations->count()) {
            $this->resetResponseSerializationContext($request);
            $event->setController(static function () use ($violations): ApiResponse {
                return new ApiResponse($violations, Response::HTTP_BAD_REQUEST);
            });
        }
    }
}
