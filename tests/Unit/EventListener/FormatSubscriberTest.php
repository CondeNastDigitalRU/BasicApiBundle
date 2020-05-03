<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Unit\EventListener;

use Condenast\BasicApiBundle\EventListener\ApiEventSubscriberInterface;
use Condenast\BasicApiBundle\EventListener\FormatSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

class FormatSubscriberTest extends TestCase
{
    use EventSubscriberTestTrait;

    /**
     * @dataProvider onKernelControllerSkipProvider
     */
    public function testOnKernelControllerSkip(Request $request): void
    {
        $subscriber = new FormatSubscriber();
        $subscriber->onKernelController($this->createControllerEventMock($request));
        $this->assertTrue(true);
    }

    public function onKernelControllerSkipProvider(): array
    {
        return [
            'Not API not JSON request with body with not empty content' => [$this->createRequestMock(
                'POST',
                '[]',
                'yaml',
                $this->createParameterBagMock()
            )],
            'API JSON request with body with not empty content' => [$this->createRequestMock(
                'POST',
                '[]',
                'json',
                $this->createParameterBagMock([ApiEventSubscriberInterface::ATTRIBUTE_API => true])
            )],
            'API not JSON request without body with not empty content' => [$this->createRequestMock(
                'GET',
                '[]',
                'yaml',
                $this->createParameterBagMock([ApiEventSubscriberInterface::ATTRIBUTE_API => true])
            )],
            'API not JSON request with body with empty content' => [$this->createRequestMock(
                'POST',
                '',
                'yaml',
                $this->createParameterBagMock([ApiEventSubscriberInterface::ATTRIBUTE_API => true])
            )],
        ];
    }

    public function testOnKernelControllerException(): void
    {
        $subscriber = new FormatSubscriber();

        $this->expectException(UnsupportedMediaTypeHttpException::class);
        $this->expectExceptionMessage('Unexpected request format "yaml", expected request format is "json"');

        $subscriber->onKernelController(
            $this->createControllerEventMock($this->createRequestMock(
                'POST',
                '[]',
                'yaml',
                $this->createParameterBagMock([ApiEventSubscriberInterface::ATTRIBUTE_API => true])
            ))
        );
    }
}
