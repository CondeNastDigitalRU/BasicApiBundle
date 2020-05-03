<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Unit\EventListener;

use Condenast\BasicApiBundle\Annotation\Action;
use Condenast\BasicApiBundle\Annotation\Request;
use Condenast\BasicApiBundle\Annotation\Response;
use Condenast\BasicApiBundle\Annotation\Validation;
use Condenast\BasicApiBundle\EventListener\ApiEventSubscriberInterface;
use Condenast\BasicApiBundle\EventListener\RequestAttributesSubscriber;
use Doctrine\Common\Annotations\Reader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequestAttributesSubscriberTest extends TestCase
{
    use EventSubscriberTestTrait;

    /**
     * @dataProvider onKernelControllerProvider
     */
    public function testOnKernelController(?Action $action, $controller, array $expectedAttributes): void
    {
        $attributes = $this->createParameterBagMock();
        $request = $this->createRequestMock(null, null, null, $attributes);
        $event = $this->createControllerEventMock($request, $controller);

        if (\count($expectedAttributes) !== 0) {
            $i = 0;
            foreach ($expectedAttributes as $name => $value) {
                $attributes
                    ->expects($this->at($i++))
                    ->method('set')
                    ->with($name, $value);
            }
        } else {
            $attributes
                ->expects($this->never())
                ->method('set');
        }

        /** @var Reader|MockObject $annotationsReader */
        $annotationsReader = $this->createMock(Reader::class);
        $annotationsReader
            ->method('getMethodAnnotation')
            ->willReturn($action);

        $subscriber = new RequestAttributesSubscriber($annotationsReader);
        $subscriber->onKernelController($event);
    }

    public function onKernelControllerProvider(): array
    {
        return [
            'All' => [
                new Action([
                    'request' => new Request([
                        'argument' => 'value',
                        'type' => \stdClass::class,
                        'context' => ['groups' => ['deserialization_group']],
                        'validation' => new Validation([
                            'groups' => ['group'],
                        ])
                    ]),
                    'response' => new Response([
                        'type' => \stdClass::class,
                        'context' => ['groups' => ['serialization_group']],
                        'statusCode' => 405,
                    ])
                ]),
                [self::class, 'controller'],
                [
                    ApiEventSubscriberInterface::ATTRIBUTE_API => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZE => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZATION_TYPE => \stdClass::class,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZATION_CONTEXT => ['groups' => ['deserialization_group']],
                    ApiEventSubscriberInterface::ATTRIBUTE_CONTROLLER_ARGUMENT => 'value',
                    ApiEventSubscriberInterface::ATTRIBUTE_VALIDATE => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_VALIDATION_GROUPS => ['group'],
                    ApiEventSubscriberInterface::ATTRIBUTE_SERIALIZATION_TYPE => \stdClass::class,
                    ApiEventSubscriberInterface::ATTRIBUTE_SERIALIZATION_CONTEXT => ['groups' => ['serialization_group']],
                    ApiEventSubscriberInterface::ATTRIBUTE_STATUS_CODE => 405,
                ],
            ],
            'Only request with validation' => [
                new Action([
                    'request' => new Request([
                        'argument' => 'value',
                        'type' => \stdClass::class,
                        'context' => ['groups' => ['deserialization_group']],
                        'validation' => new Validation([
                            'groups' => ['group'],
                        ])
                    ]),
                ]),
                [self::class, 'controller'],
                [
                    ApiEventSubscriberInterface::ATTRIBUTE_API => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZE => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZATION_TYPE => \stdClass::class,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZATION_CONTEXT => ['groups' => ['deserialization_group']],
                    ApiEventSubscriberInterface::ATTRIBUTE_CONTROLLER_ARGUMENT => 'value',
                    ApiEventSubscriberInterface::ATTRIBUTE_VALIDATE => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_VALIDATION_GROUPS => ['group'],
                ],
            ],
            'Only request without validation' => [
                new Action([
                    'request' => new Request([
                        'argument' => 'value',
                        'type' => \stdClass::class,
                        'context' => ['groups' => ['deserialization_group']],
                    ]),
                ]),
                [self::class, 'controller'],
                [
                    ApiEventSubscriberInterface::ATTRIBUTE_API => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZE => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZATION_TYPE => \stdClass::class,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZATION_CONTEXT => ['groups' => ['deserialization_group']],
                    ApiEventSubscriberInterface::ATTRIBUTE_CONTROLLER_ARGUMENT => 'value',
                ],
            ],
            'Only response' => [
                new Action([
                    'response' => new Response([
                        'type' => \stdClass::class,
                        'context' => ['groups' => ['serialization_group']],
                        'statusCode' => 405,
                    ]),
                ]),
                [self::class, 'controller'],
                [
                    ApiEventSubscriberInterface::ATTRIBUTE_API => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_SERIALIZATION_TYPE => \stdClass::class,
                    ApiEventSubscriberInterface::ATTRIBUTE_SERIALIZATION_CONTEXT => ['groups' => ['serialization_group']],
                    ApiEventSubscriberInterface::ATTRIBUTE_STATUS_CODE => 405,
                ],
            ],
            'Nothing' => [
                null,
                [self::class, 'controller'],
                [],
            ],
            'All with invocable controller' => [
                new Action([
                    'request' => new Request([
                        'argument' => 'value',
                        'type' => \stdClass::class,
                        'context' => ['groups' => ['deserialization_group']],
                        'validation' => new Validation([
                            'groups' => ['group'],
                        ])
                    ]),
                    'response' => new Response([
                        'type' => \stdClass::class,
                        'context' => ['groups' => ['serialization_group']],
                        'statusCode' => 405,
                    ])
                ]),
                new class {
                    public function __invoke(): void
                    {
                    }
                },
                [
                    ApiEventSubscriberInterface::ATTRIBUTE_API => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZE => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZATION_TYPE => \stdClass::class,
                    ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZATION_CONTEXT => ['groups' => ['deserialization_group']],
                    ApiEventSubscriberInterface::ATTRIBUTE_CONTROLLER_ARGUMENT => 'value',
                    ApiEventSubscriberInterface::ATTRIBUTE_VALIDATE => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_VALIDATION_GROUPS => ['group'],
                    ApiEventSubscriberInterface::ATTRIBUTE_SERIALIZATION_TYPE => \stdClass::class,
                    ApiEventSubscriberInterface::ATTRIBUTE_SERIALIZATION_CONTEXT => ['groups' => ['serialization_group']],
                    ApiEventSubscriberInterface::ATTRIBUTE_STATUS_CODE => 405,
                ],
            ],
        ];
    }

    public static function controller(): void
    {
    }
}
