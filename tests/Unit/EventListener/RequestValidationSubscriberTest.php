<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Unit\EventListener;

use Condenast\BasicApiBundle\EventListener\RequestValidationSubscriber;
use Condenast\BasicApiBundle\Response\Payload;
use Condenast\BasicApiBundle\Tests\Unit\ObjectMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RequestValidationSubscriberTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_validate_the_value_deserialized_from_the_request(): void
    {
        $deserialization = ObjectMother::deserialization();
        $validation = ObjectMother::validation();
        $deserialized = new \stdClass();
        $event = ObjectMother::controllerEvent(
            ObjectMother::deserializationRequest($deserialization, $validation, [$deserialization->getArgument() => $deserialized])
        );

        $violations = ObjectMother::constraintViolationList();
        $validator = $this->createMock(ValidatorInterface::class);
        $validator
            ->expects(self::once())
            ->method('validate')
            ->with($deserialized, null, $validation->getGroups())
            ->willReturn($violations);

        $subscriber = new RequestValidationSubscriber($validator);

        $subscriber->onKernelController($event);

        $controller = $event->getController();
        /** @var Payload $payload */
        $payload = $controller();

        self::assertInstanceOf(\Closure::class, $controller);
        self::assertInstanceOf(Payload::class, $payload);
        self::assertSame(400, $payload->getStatus());
        self::assertSame($violations, $payload->getData());
    }

    /**
     * @test
     * @dataProvider skippedRequests
     */
    public function it_skips_if_the_request_does_not_meet_the_requirements(Request $request): void
    {
        $event = ObjectMother::controllerEvent($request);
        $controller = $event->getController();

        $validator = $this->createMock(ValidatorInterface::class);
        $validator
            ->expects(self::never())
            ->method('validate');

        $subscriber = new RequestValidationSubscriber($validator);

        $subscriber->onKernelController($event);

        self::assertSame($controller, $event->getController());
    }

    /**
     * @test
     */
    public function it_skips_if_there_are_no_validation_errors(): void
    {
        $event = ObjectMother::controllerEvent(ObjectMother::deserializationRequest(
            ObjectMother::deserialization(),
            ObjectMother::validation(),
            [ObjectMother::deserialization()->getArgument() => 'value']
        ));
        $controller = $event->getController();

        $validator = $this->createMock(ValidatorInterface::class);
        $validator
            ->method('validate')
            ->willReturn(ObjectMother::emptyConstraintViolationList());

        $subscriber = new RequestValidationSubscriber($validator);

        $subscriber->onKernelController($event);

        self::assertSame($controller, $event->getController());
    }

    public function skippedRequests(): array
    {
        return [
            'Request without deserialization annotation' => [ObjectMother::deserializationRequest(null, ObjectMother::validation())],
            'Request without validation annotation' => [
                ObjectMother::deserializationRequest(
                    ObjectMother::deserialization(),
                    null,
                    [ObjectMother::deserialization()->getArgument() => 'value']
                )
            ],
            'Request without deserialized value' => [ObjectMother::deserializationRequest(ObjectMother::deserialization(), ObjectMother::validation())],
        ];
    }
}
