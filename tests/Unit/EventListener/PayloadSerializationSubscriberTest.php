<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Unit\EventListener;

use Condenast\BasicApiBundle\EventListener\PayloadSerializationSubscriber;
use Condenast\BasicApiBundle\Tests\Unit\ObjectMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Serializer\SerializerInterface;

final class PayloadSerializationSubscriberTest extends TestCase
{
    /**
     * @test
     */
    public function it_serializes_the_payload_and_sets_the_response(): void
    {
        $payload = ObjectMother::payload();
        $event = ObjectMother::viewEvent(ObjectMother::apiRequest(), $payload);

        $serialized = '["data"]';
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects(self::once())
            ->method('serialize')
            ->with($payload->getData(), 'json', $payload->getSerializationContext())
            ->willReturn($serialized);

        $subscriber = new PayloadSerializationSubscriber($serializer);

        $subscriber->onKernelView($event);

        self::assertEquals(
            new Response(
                $serialized,
                $payload->getStatus(),
                \array_merge($payload->getHeaders(), ['Content-Type' => 'application/json'])
            ),
            $event->getResponse()
        );
    }

    /**
     * @test
     */
    public function it_does_not_override_the_content_type_header(): void
    {
        $payload = ObjectMother::payloadWithContentTypeHeader();
        $event = ObjectMother::viewEvent(ObjectMother::apiRequest(), $payload);

        $serialized = '["data"]';
        $serializer = $this->createConfiguredMock(SerializerInterface::class, [
            'serialize' => $serialized,
        ]);

        $subscriber = new PayloadSerializationSubscriber($serializer);

        $subscriber->onKernelView($event);

        self::assertSame($payload->getHeaders(), $event->getResponse()->headers->all());
    }

    /**
     * @test
     */
    public function it_does_not_add_the_content_type_json_header_to_the_payload_with_null_data(): void
    {
        $payload = ObjectMother::payloadWithNullData();
        $event = ObjectMother::viewEvent(ObjectMother::apiRequest(), $payload);

        $subscriber = new PayloadSerializationSubscriber($this->createMock(SerializerInterface::class));

        $subscriber->onKernelView($event);

        self::assertSame($payload->getHeaders(), $event->getResponse()->headers->all());
    }

    /**
     * @test
     * @dataProvider skippedEvents
     */
    public function it_skips_if_the_requirements_are_not_met(ViewEvent $event): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects(self::never())
            ->method('serialize');

        $subscriber = new PayloadSerializationSubscriber($serializer);

        $subscriber->onKernelView($event);

        self::assertNull($event->getResponse());
    }

    public function skippedEvents(): array
    {
        return [
            'Controller result is not a payload' => [
                ObjectMother::viewEvent(ObjectMother::apiRequest(), new \stdClass())
            ],
            'Request is not an API request' => [
                ObjectMother::viewEvent(ObjectMother::notApiRequest(), ObjectMother::payload())
            ],
        ];
    }
}
