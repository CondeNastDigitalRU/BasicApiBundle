<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Unit\EventListener;

use Condenast\BasicApiBundle\EventListener\ApiEventSubscriberInterface;
use Condenast\BasicApiBundle\EventListener\RequestDeserializationSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;

class RequestDeserializationSubscriberTest extends TestCase
{
    use EventSubscriberTestTrait;

    /**
     * @dataProvider onKernelControllerProvider
     */
    public function testOnKernelController(string $method, $content, array $attributes, bool $deserialize, $deserialized): void
    {
        /** @var SerializerInterface|MockObject $serializer */
        $serializer = $this->createMock(SerializerInterface::class);
        $attributesBag = $this->createParameterBagMock($attributes);
        $request = $this->createRequestMock($method, $content, null, $attributesBag);
        $event = $this->createControllerEventMock($request);

        if ($deserialize) {
            $serializer
                ->expects($this->once())
                ->method('deserialize')
                ->with(
                    $content,
                    $attributes[ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZATION_TYPE],
                    'json',
                    $attributes[ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZATION_CONTEXT] ?? []
                )
                ->willReturn($deserialized)
            ;

            $attributesBag
                ->expects($this->once())
                ->method('set')
                ->with($attributes[ApiEventSubscriberInterface::ATTRIBUTE_CONTROLLER_ARGUMENT], $deserialized);
        } else {
            $serializer
                ->expects($this->never())
                ->method('deserialize');
        }

        $subscriber = new RequestDeserializationSubscriber($serializer);
        $subscriber->onKernelController($event);
    }

    public function onKernelControllerProvider(): array
    {
        return [
            // string $method, $content, array $attributes, array $attributesExpected, bool $deserialize, $deserialized
            'API POST request with deserialization enabled and not empty content' => [
                'POST',
                '[]',
                [
                    ApiEventSubscriberInterface::ATTRIBUTE_API => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZE => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZATION_TYPE => \stdClass::class,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZATION_CONTEXT => ['groups' => ['group']],
                    ApiEventSubscriberInterface::ATTRIBUTE_CONTROLLER_ARGUMENT => 'value',
                ],
                true,
                new \stdClass()
            ],
            'API PUT request with deserialization enabled but without deserialization context and not empty content' => [
                'PUT',
                '[]',
                [
                    ApiEventSubscriberInterface::ATTRIBUTE_API => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZE => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZATION_TYPE => \stdClass::class,
                    ApiEventSubscriberInterface::ATTRIBUTE_CONTROLLER_ARGUMENT => 'value',
                ],
                true,
                new \stdClass()
            ],
            'API PATCH request with deserialization enabled and not empty content' => [
                'PATCH',
                '[]',
                [
                    ApiEventSubscriberInterface::ATTRIBUTE_API => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZE => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZATION_TYPE => \stdClass::class,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZATION_CONTEXT => ['groups' => ['group']],
                    ApiEventSubscriberInterface::ATTRIBUTE_CONTROLLER_ARGUMENT => 'value',
                ],
                true,
                new \stdClass()
            ],
            'API DELETE request with deserialization enabled and not empty content' => [
                'DELETE',
                '[]',
                [
                    ApiEventSubscriberInterface::ATTRIBUTE_API => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZE => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZATION_TYPE => \stdClass::class,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZATION_CONTEXT => ['groups' => ['group']],
                    ApiEventSubscriberInterface::ATTRIBUTE_CONTROLLER_ARGUMENT => 'value',
                ],
                true,
                new \stdClass()
            ],
            'API POST request with content and deserialization disabled' => [
                'POST',
                '[]',
                [
                    ApiEventSubscriberInterface::ATTRIBUTE_API => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZE => null,
                ],
                false,
                null
            ],
            'API POST request with deserialization enabled and empty content' => [
                'POST',
                '',
                [
                    ApiEventSubscriberInterface::ATTRIBUTE_API => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZE => true,
                ],
                false,
                null
            ],
            'Not API POST request with deserialization enabled and not empty content' => [
                'POST',
                '[]',
                [
                    ApiEventSubscriberInterface::ATTRIBUTE_API => false,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZE => true,
                ],
                false,
                null
            ],
            'API GET request with deserialization enabled and not empty content' => [
                'GET',
                '[]',
                [
                    ApiEventSubscriberInterface::ATTRIBUTE_API => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZE => true,
                ],
                false,
                new \stdClass()
            ],
        ];
    }

    /**
     * @dataProvider onKernelControllerSerializerExceptionsProvider
     */
    public function testOnKernelControllerSerializerException(\Throwable $serializerException, string $subscriberException, string $subscriberExceptionMessage): void
    {
        /** @var SerializerInterface|MockObject $serializer */
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->willThrowException($serializerException);

        $attributesBag = $this->createParameterBagMock([
            ApiEventSubscriberInterface::ATTRIBUTE_API => true,
            ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZE => true,
            ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZATION_TYPE => \stdClass::class,
            ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZATION_CONTEXT => ['groups' => ['group']],
            ApiEventSubscriberInterface::ATTRIBUTE_CONTROLLER_ARGUMENT => 'value',
        ]);
        $request = $this->createRequestMock('POST', '[]', null, $attributesBag);
        $event = $this->createControllerEventMock($request);

        $this->expectException($subscriberException);
        $this->expectExceptionMessage($subscriberExceptionMessage);

        $subscriber = new RequestDeserializationSubscriber($serializer);
        $subscriber->onKernelController($event);
    }

    public function onKernelControllerSerializerExceptionsProvider(): array
    {
        return [
            [$this->createMock(NotEncodableValueException::class), BadRequestHttpException::class, 'Request does not contain valid json'],
            [$this->createMock(ExceptionInterface::class), BadRequestHttpException::class, 'Request can\'t be deserialized'],
        ];
    }
}
