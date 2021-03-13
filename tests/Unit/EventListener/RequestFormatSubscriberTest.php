<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Unit\EventListener;

use Condenast\BasicApiBundle\EventListener\RequestFormatSubscriber;
use Condenast\BasicApiBundle\Tests\Unit\ObjectMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

final class RequestFormatSubscriberTest extends TestCase
{
    /**
     * @test
     * @dataProvider matchingRequests
     */
    public function it_skips_matching_request(Request $request): void
    {
        $event = ObjectMother::controllerEvent($request);

        $subscriber = new RequestFormatSubscriber();

        $subscriber->onKernelController($event);

        self::assertTrue(true);
    }

    /**
     * @test
     * @dataProvider inappropriateRequests
     */
    public function it_throws_an_error_on_an_inappropriate_request(Request $request): void
    {
        $event = ObjectMother::controllerEvent($request);

        $this->expectException(UnsupportedMediaTypeHttpException::class);
        $this->expectExceptionMessage(\sprintf(
            'Unexpected request format "%s", expected request format is "json". Consider adding a json body and a Content-Type header to your request, such as "application/json"',
            $request->getContentType() ?? 'unknown'
        ));

        $subscriber = new RequestFormatSubscriber();

        $subscriber->onKernelController($event);
    }

    public function matchingRequests(): array
    {
        return [
            'JSON request' => [ObjectMother::jsonRequest()],
            'Request without deserialization' => [ObjectMother::deserializationRequest()],
        ];
    }

    public function inappropriateRequests(): array
    {
        return [
            'Not JSON request with deserialization' => [ObjectMother::deserializationRequest(ObjectMother::deserialization())]
        ];
    }
}
