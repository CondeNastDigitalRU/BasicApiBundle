<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Unit\EventListener;

use Condenast\BasicApiBundle\EventListener\ApiEventSubscriberInterface;
use Condenast\BasicApiBundle\EventListener\ErrorSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;

class ErrorSubscriberTest extends TestCase
{
    use EventSubscriberTestTrait;

    public function testOnKernelExceptionSkip(): void
    {
        $event = $this->createExceptionEvent($this->createRequestMock(null, null, null, $this->createParameterBagMock()));

        /** @var ErrorListener|MockObject $errorListener */
        $errorListener = $this->createMock(ErrorListener::class);
        $errorListener
            ->expects($this->never())
            ->method('onKernelException');

        $subscriber = new ErrorSubscriber($errorListener);
        $subscriber->onKernelException($event);
    }

    public function testOnKernelException(): void
    {
        $attributes = $this->createParameterBagMock([
            ApiEventSubscriberInterface::ATTRIBUTE_API => true,
            ApiEventSubscriberInterface::ATTRIBUTE_SERIALIZATION_CONTEXT => ['groups' => ['group']],
        ]);
        $request = $this->createRequestMock(
            null,
            null,
            null,
            $attributes
        );
        $event = $this->createExceptionEvent($request);

        /** @var ErrorListener|MockObject $errorListener */
        $errorListener = $this->createMock(ErrorListener::class);
        $errorListener
            ->expects($this->once())
            ->method('onKernelException')
            ->with($event);

        $attributes
            ->expects($this->once())
            ->method('set')
            ->with(ApiEventSubscriberInterface::ATTRIBUTE_SERIALIZATION_CONTEXT, []);

        $subscriber = new ErrorSubscriber($errorListener);
        $subscriber->onKernelException($event);
    }
}
