<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Unit\EventListener;

use Condenast\BasicApiBundle\EventListener\RequestDeserializationSubscriber;
use Condenast\BasicApiBundle\Tests\Unit\ObjectMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;

final class RequestDeserializationSubscriberTest extends TestCase
{
    /**
     * @test
     */
    public function it_deserializes_the_request_and_sets_the_appropriate_attribute(): void
    {
        $deserialization = ObjectMother::deserialization();
        $request = ObjectMother::deserializationRequest($deserialization);
        $event = ObjectMother::controllerEvent($request);

        $deserialized = new class {
            public $property;
        };
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects(self::once())
            ->method('deserialize')
            ->with($request->getContent(), $deserialization->getType(), 'json', $deserialization->getContext())
            ->willReturn($deserialized);

        $subscriber = new RequestDeserializationSubscriber($serializer, ObjectMother::propertyAccessor());

        $subscriber->onKernelController($event);

        self::assertSame($request->attributes->get($deserialization->getArgument()), $deserialized);
    }

    /**
     * @test
     */
    public function it_can_set_the_value_of_the_request_attribute_to_a_property_of_the_deserialized_object(): void
    {
        $deserialization = ObjectMother::deserialization();
        $attributeValue = 'value';
        $request = ObjectMother::deserializationRequest($deserialization, null, ['attribute' => $attributeValue]);
        $event = ObjectMother::controllerEvent($request);

        $deserialized = new class {
            public $property;
        };
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->method('deserialize')
            ->willReturn($deserialized);

        $subscriber = new RequestDeserializationSubscriber($serializer, ObjectMother::propertyAccessor());

        $subscriber->onKernelController($event);

        self::assertSame($attributeValue, $deserialized->property);
    }

    /**
     * @test
     */
    public function it_skips_if_there_is_no_deserialization_annotation_in_request(): void
    {
        $request = ObjectMother::deserializationRequest();
        $attributes = $request->attributes->all();
        $event = ObjectMother::controllerEvent($request);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects(self::never())
            ->method('deserialize');

        $subscriber = new RequestDeserializationSubscriber($serializer, ObjectMother::propertyAccessor());

        $subscriber->onKernelController($event);

        self::assertSame($attributes, $request->attributes->all());
    }

    /**
     * @test
     */
    public function if_the_attribute_is_already_set_then_this_will_result_in_an_error(): void
    {
        $deserialization = ObjectMother::deserialization();
        $event = ObjectMother::controllerEvent(ObjectMother::deserializationRequest($deserialization, null, [$deserialization->getArgument() => 'value']));

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects(self::never())
            ->method('deserialize');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf('An attribute with name "%s" is already present in the request', $deserialization->getArgument()));

        $subscriber = new RequestDeserializationSubscriber($serializer, ObjectMother::propertyAccessor());

        $subscriber->onKernelController($event);
    }

    /**
     * @test
     * @dataProvider serializerExceptions
     */
    public function it_can_handle_serializer_exceptions(
        \Throwable $serializerException,
        string $expectedException,
        string $expectedExceptionMessage
    ): void {
        $deserialization = ObjectMother::deserialization();
        $request = ObjectMother::deserializationRequest($deserialization);
        $event = ObjectMother::controllerEvent($request);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('deserialize')->willThrowException($serializerException);

        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $subscriber = new RequestDeserializationSubscriber($serializer, ObjectMother::propertyAccessor());

        $subscriber->onKernelController($event);
    }

    public function serializerExceptions(): array
    {
        return [
            [$this->createMock(NotEncodableValueException::class), BadRequestHttpException::class, 'Request does not contain valid json'],
            [$this->createMock(ExceptionInterface::class), BadRequestHttpException::class, 'Request can\'t be deserialized'],
            [$this->createMock(\TypeError::class), BadRequestHttpException::class, 'Request can\'t be deserialized'],
        ];
    }
}
