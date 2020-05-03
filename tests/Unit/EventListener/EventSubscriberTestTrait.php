<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Unit\EventListener;

use Condenast\BasicApiBundle\Response\ApiResponse;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;

trait EventSubscriberTestTrait
{
    /**
     * @return ParameterBag|MockObject
     */
    private function createParameterBagMock(array $attributes = []): ParameterBag
    {
        /** @var ParameterBag|MockObject $bag */
        $bag = $this->createMock(ParameterBag::class);
        $returnMap = [];
        foreach ($attributes as $name => $value) {
            $returnMap[] = [$name, null, $value];
        }

        $bag
            ->method('get')
            ->willReturnMap($returnMap);

        return $bag;
    }

    /**
     * @return HeaderBag|MockObject
     */
    private function createHeaderBagMock(array $headers = []): HeaderBag
    {
        /** @var HeaderBag|MockObject $bag */
        $bag = $this->createMock(HeaderBag::class);
        $returnMap = [];
        foreach ($headers as $name => $value) {
            $returnMap[] = [$name, null, $value];
        }

        $bag
            ->method('get')
            ->willReturnMap($returnMap);

        return $bag;
    }

    /**
     * @return Request|MockObject
     */
    private function createRequestMock(?string $method = null, ?string $content = null, ?string $format = null, ?ParameterBag $attributes = null, ?HeaderBag $headers = null): Request
    {
        /** @var Request|MockObject $request */
        $request = $this->createMock(Request::class);

        if (null !== $method) {
            $request
                ->method('getMethod')
                ->willReturn($method);
        }

        if (null !== $content) {
            $request
                ->method('getContent')
                ->willReturn($content);
        }

        if (null !== $format) {
            $request
                ->method('getContentType')
                ->willReturn($format);
        }

        $request->attributes = $attributes;
        $request->headers = $headers;

        return $request;
    }

    /**
     * @return ApiResponse|MockObject
     */
    private function createApiResponseMock($data = null, ?int $statusCode = null, ?HeaderBag $headers = null): ApiResponse
    {
        /** @var ApiResponse|MockObject $response */
        $response = $this->createMock(ApiResponse::class);
        if (null !== $data) {
            $response
                ->method('getData')
                ->willReturn($data);
        }

        $response
            ->method('isStatusCodeSet')
            ->willReturn(null !== $statusCode);

        if (null !== $statusCode) {
            $response
                ->method('getStatusCode')
                ->willReturn($statusCode);
        }

        $response->headers = $headers;

        return $response;
    }

    /**
     * @return Response|MockObject
     */
    private function createResponseMock(?string $content = null, ?HeaderBag $headers = null): Response
    {
        /** @var Response|MockObject $response */
        $response = $this->createMock(Response::class);

        if (null !== $content) {
            $response
                ->method('getContent')
                ->willReturn($content);
        }

        $response->headers = $headers;

        return $response;
    }

    /**
     * @return ControllerEvent|MockObject
     */
    private function createControllerEventMock(?Request $request = null, $controller = null): ControllerEvent
    {
        /** @var ControllerEvent|MockObject $event */
        $event = $this->createMock(ControllerEvent::class);

        if (null !== $request) {
            $event
                ->method('getRequest')
                ->willReturn($request);
        }

        if (null !== $controller) {
            $event
                ->method('getController')
                ->willReturn($controller);
        }

        return $event;
    }

    private function createExceptionEvent(?Request $request = null): ExceptionEvent
    {
        /** @var ExceptionEvent|MockObject $event */
        $event = $this->createMock(ExceptionEvent::class);

        if (null !== $request) {
            $event
                ->method('getRequest')
                ->willReturn($request);
        }

        return $event;
    }

    /**
     * @return ViewEvent|MockObject
     */
    private function createViewEventMock(?Request $request = null, $controllerResult = null): ViewEvent
    {
        /** @var ViewEvent|MockObject $event */
        $event = $this->createMock(ViewEvent::class);

        if (null !== $request) {
            $event
                ->method('getRequest')
                ->willReturn($request);
        }

        if (null !== $controllerResult) {
            $event
                ->method('getControllerResult')
                ->willReturn($controllerResult);
        }


        return $event;
    }

    private function createResponseEventMock(?Request $request = null, ?Response $response = null): ResponseEvent
    {
        /** @var ResponseEvent|MockObject $event */
        $event = $this->createMock(ResponseEvent::class);

        if (null !== $request) {
            $event
                ->method('getRequest')
                ->willReturn($request);
        }

        if (null !== $response) {
            $event
                ->method('getResponse')
                ->willReturn($response);
        }

        return $event;
    }
}
