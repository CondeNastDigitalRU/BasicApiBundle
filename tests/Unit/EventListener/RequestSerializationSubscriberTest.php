<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Unit\EventListener;

use Condenast\BasicApiBundle\EventListener\ApiEventSubscriberInterface;
use Condenast\BasicApiBundle\EventListener\ResponseSerializationSubscriber;
use Condenast\BasicApiBundle\Response\ApiResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class RequestSerializationSubscriberTest extends TestCase
{
    use EventSubscriberTestTrait;

    /**
     * @dataProvider onKernelViewProvider
     */
    public function testOnKernelView(array $attributes, $controllerResult, bool $shouldWrap): void
    {
        $attributesBag = $this->createParameterBagMock($attributes);
        $request = $this->createRequestMock('POST', '[]', null, $attributesBag);
        $event = $this->createViewEventMock($request, $controllerResult);
        /** @var SerializerInterface|MockObject $serializer */
        $serializer = $this->createMock(SerializerInterface::class);

        if ($shouldWrap) {
            $event
                ->expects($this->once())
                ->method('setResponse')
                ->with($this->callback(static function ($response) use ($attributes, $controllerResult) {
                    return $response instanceof ApiResponse
                        && $response->getData() === $controllerResult;
                }));
        } else {
            $event
                ->expects($this->never())
                ->method('setResponse');
        }

        $subscriber = new ResponseSerializationSubscriber($serializer);
        $subscriber->onKernelView($event);
    }

    public function onKernelViewProvider(): array
    {
        return [
            'API request' => [
                [ApiEventSubscriberInterface::ATTRIBUTE_API => true],
                ['response'],
                true
            ],
            'Not API request' => [
                [ApiEventSubscriberInterface::ATTRIBUTE_API => null],
                null,
                false
            ]
        ];
    }

    /**
     * @param Request|MockObject $request
     * @param Response|MockObject $response
     * @param HeaderBag|MockObject $responseHeaders
     * @dataProvider onKernelResponseSkipProvider
     */
    public function testOnKernelResponseSkip(Request $request, Response $response, HeaderBag $responseHeaders): void
    {
        /** @var SerializerInterface|MockObject $serializer */
        $serializer = $this->createMock(SerializerInterface::class);

        $serializer->expects($this->never())->method('serialize');
        $response->expects($this->never())->method('setStatusCode');
        $response->expects($this->never())->method('setContent');
        $responseHeaders->expects($this->never())->method('set');

        $subscriber = new ResponseSerializationSubscriber($serializer);
        $subscriber->onKernelResponse($this->createResponseEventMock($request, $response));
    }

    public function onKernelResponseSkipProvider(): array
    {
        return [
            'API request and not API response' => [
                $this->createRequestMock(null, null, null, $this->createParameterBagMock([ApiEventSubscriberInterface::ATTRIBUTE_API => true])),
                $response = $this->createResponseMock(null, $headers = $this->createHeaderBagMock()),
                $headers,
            ],
            'Not API request and API response with not empty data' => [
                $this->createRequestMock(null, null, null, $this->createParameterBagMock()),
                $apiResponse = $this->createApiResponseMock([], null, $headers = $this->createHeaderBagMock()),
                $headers,
            ],
            'API request and API response with empty data' => [
                $this->createRequestMock(null, null, null, $this->createParameterBagMock([ApiEventSubscriberInterface::ATTRIBUTE_API => true])),
                $apiResponse = $this->createApiResponseMock(null, 200, $headers = $this->createHeaderBagMock()),
                $headers,
            ]
        ];
    }

    public function testOnKernelResponse(): void
    {
        $requestAttributes = [
            ApiEventSubscriberInterface::ATTRIBUTE_API => true,
            ApiEventSubscriberInterface::ATTRIBUTE_SERIALIZATION_CONTEXT => ['groups' => ['group']],
            ApiEventSubscriberInterface::ATTRIBUTE_STATUS_CODE => 405,
        ];
        $request = $this->createRequestMock(null, null, null, $this->createParameterBagMock($requestAttributes));
        $responseData = [];
        $responseHeaders = $this->createHeaderBagMock();
        $response = $this->createApiResponseMock($responseData, null, $responseHeaders);

        $serialized = '[]';
        /** @var SerializerInterface|MockObject $serializer */
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('serialize')
            ->with(
                $responseData,
                'json',
                $requestAttributes[ApiEventSubscriberInterface::ATTRIBUTE_SERIALIZATION_CONTEXT]
            )
            ->willReturn($serialized)
        ;

        $response
            ->expects($this->once())
            ->method('setContent')
            ->with($serialized);

        $response
            ->expects($this->once())
            ->method('setStatusCode')
            ->with($requestAttributes[ApiEventSubscriberInterface::ATTRIBUTE_STATUS_CODE]);

        $responseHeaders
            ->expects($this->once())
            ->method('set')
            ->with('Content-Type', 'application/json');

        $subscriber = new ResponseSerializationSubscriber($serializer);
        $subscriber->onKernelResponse($this->createResponseEventMock($request, $response));
    }
}
