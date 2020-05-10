<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Unit\EventListener;

use Condenast\BasicApiBundle\EventListener\ApiEventSubscriberInterface;
use Condenast\BasicApiBundle\EventListener\RequestValidationSubscriber;
use Condenast\BasicApiBundle\Response\ApiResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestValidationSubscriberTest extends TestCase
{
    use EventSubscriberTestTrait;

    /**
     * @dataProvider onKernelControllerProvider
     */
    public function testOnKernelController(array $attributes, bool $validate, int $violationsCount): void
    {
        /** @var ValidatorInterface|MockObject $validator */
        $validator = $this->createMock(ValidatorInterface::class);
        $attributesBag = $this->createParameterBagMock($attributes);
        $request = $this->createRequestMock('POST', '', null, $attributesBag);
        $event = $this->createControllerEventMock($request);

        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations
            ->method('count')
            ->willReturn($violationsCount);

        if ($validate) {
            $deserialized = $attributes[$attributes[ApiEventSubscriberInterface::ATTRIBUTE_CONTROLLER_ARGUMENT]];

            $validator
                ->expects($this->once())
                ->method('validate')
                ->with(
                    $deserialized,
                    null,
                    $this->callback(static function ($groups) use ($attributes) {
                        if (true === ($attributes[ApiEventSubscriberInterface::ATTRIBUTE_VALIDATION_GROUP_SEQUENCE] ?? false)) {
                            return $groups instanceof GroupSequence && $groups->groups === ($attributes[ApiEventSubscriberInterface::ATTRIBUTE_VALIDATION_GROUPS] ?? []);
                        }

                        return $groups === ($attributes[ApiEventSubscriberInterface::ATTRIBUTE_VALIDATION_GROUPS] ?? []);
                    })
                )
                ->willReturn($violations);

            if ($violationsCount > 0) {
                $event
                    ->expects($this->once())
                    ->method('setController')
                    ->with($this->callback(static function ($controller) use ($violations) {
                        return \is_callable($controller)
                            && ($apiResponse = $controller()) instanceof ApiResponse
                            && $apiResponse->getData() === $violations;
                    }));
                $attributesBag
                    ->expects($this->once())
                    ->method('set')
                    ->with(ApiEventSubscriberInterface::ATTRIBUTE_SERIALIZATION_CONTEXT, []);
            }
        } else {
            $validator
                ->expects($this->never())
                ->method('validate');
        }

        $subscriber = new RequestValidationSubscriber($validator);
        $subscriber->onKernelController($event);
    }

    public function onKernelControllerProvider(): array
    {
        return [
            'API request with validation enabled, not empty argument and validation errors' => [
                [
                    ApiEventSubscriberInterface::ATTRIBUTE_API => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_VALIDATE => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_VALIDATION_GROUPS => ['group'],
                    ApiEventSubscriberInterface::ATTRIBUTE_CONTROLLER_ARGUMENT => 'value',
                    'value' => new \stdClass(),
                ],
                true,
                1
            ],
            'API request with validation and group sequence enabled, not empty argument and validation errors' => [
                [
                    ApiEventSubscriberInterface::ATTRIBUTE_API => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_VALIDATE => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_VALIDATION_GROUPS => ['group'],
                    ApiEventSubscriberInterface::ATTRIBUTE_VALIDATION_GROUP_SEQUENCE => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_CONTROLLER_ARGUMENT => 'value',
                    'value' => new \stdClass(),
                ],
                true,
                1
            ],
            'API request with validation enabled, without validation groups, not empty array argument and validation errors' => [
                [
                    ApiEventSubscriberInterface::ATTRIBUTE_API => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_VALIDATE => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_CONTROLLER_ARGUMENT => 'value',
                    'value' => [new \stdClass()],
                ],
                true,
                1
            ],
            'API request with validation enabled, not empty argument and no validation errors' => [
                [
                    ApiEventSubscriberInterface::ATTRIBUTE_API => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_VALIDATE => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_VALIDATION_GROUPS => ['group'],
                    ApiEventSubscriberInterface::ATTRIBUTE_CONTROLLER_ARGUMENT => 'value',
                    'value' => new \stdClass(),
                ],
                true,
                0
            ],
            'API request with validation enabled and empty argument' => [
                [
                    ApiEventSubscriberInterface::ATTRIBUTE_API => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_VALIDATE => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_VALIDATION_GROUPS => ['group'],
                    ApiEventSubscriberInterface::ATTRIBUTE_CONTROLLER_ARGUMENT => 'value',
                    'value' => null,
                ],
                false,
                0
            ],
            'API request with validation disabled' => [
                [
                    ApiEventSubscriberInterface::ATTRIBUTE_API => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_VALIDATE => false,
                    ApiEventSubscriberInterface::ATTRIBUTE_CONTROLLER_ARGUMENT => 'value',
                    'value' => new \stdClass(),
                ],
                false,
                0
            ],
            'Not API request with validation enabled and not empty argument' => [
                [
                    ApiEventSubscriberInterface::ATTRIBUTE_API => null,
                    ApiEventSubscriberInterface::ATTRIBUTE_VALIDATE => true,
                    ApiEventSubscriberInterface::ATTRIBUTE_VALIDATION_GROUPS => ['group'],
                    ApiEventSubscriberInterface::ATTRIBUTE_CONTROLLER_ARGUMENT => 'value',
                    'value' => null,
                ],
                false,
                0
            ]
        ];
    }
}
