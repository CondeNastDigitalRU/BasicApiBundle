<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Unit;

use Condenast\BasicApiBundle\Annotation\Deserialization;
use Condenast\BasicApiBundle\Annotation\QueryParam;
use Condenast\BasicApiBundle\Annotation\Validation;
use Condenast\BasicApiBundle\EventListener\RequestConfigurationSubscriber;
use Condenast\BasicApiBundle\Response\Payload;
use PHPUnit\Framework\MockObject\Generator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;

final class ObjectMother
{
    public static function payload(): Payload
    {
        return new Payload(['data'], 201, ['groups' => ['group']], ['header' => 'headerValue']);
    }

    public static function payloadWithContentTypeHeader(): Payload
    {
        return new Payload(['data'], 201, ['groups' => ['group']], ['Content-Type' => 'application/vnd.api+json']);
    }

    public static function payloadWithNullData(): Payload
    {
        return new Payload(null);
    }

    public static function apiRequest(): Request
    {
        return new Request([], [], [RequestConfigurationSubscriber::ATTRIBUTE_API => true]);
    }

    public static function notApiRequest(): Request
    {
        return new Request();
    }

    public static function jsonRequest(): Request
    {
        $request = new Request();
        $request->headers->set('Content-Type', 'application/json');

        return $request;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function deserializationRequest(
        ?Deserialization $deserialization = null,
        ?Validation $validation = null,
        array $attributes = []
    ): Request {
        return new Request(
            [],
            [],
            \array_merge(
                [
                    RequestConfigurationSubscriber::ATTRIBUTE_API_DESERIALIZATION => $deserialization,
                    RequestConfigurationSubscriber::ATTRIBUTE_API_VALIDATION => $validation,
                ],
                $attributes
            )
        );
    }

    /**
     * @param array<QueryParam> $queryParams
     * @param array<string, mixed> $query
     * @param array<string, mixed> $attributes
     */
    public static function queryParamsRequest(array $queryParams = [], array $query = [], array $attributes = []): Request
    {
        return new Request(
            $query,
            [],
            \array_merge(
                [
                    RequestConfigurationSubscriber::ATTRIBUTE_API_QUERY_PARAMS => $queryParams
                ],
                $attributes
            )
        );
    }

    public static function deserialization(): Deserialization
    {
        return new Deserialization([
            'argument' => 'argument',
            'type' => 'ClassName',
            'context' => ['groups' => ['group']],
        ]);
    }

    public static function validation(): Validation
    {
        return new Validation(['groups' => ['group']]);
    }

    public static function queryParam(): QueryParam
    {
        return new QueryParam([
            'name' => 'name',
            'path' => 'path',
            'default' => 'default',
        ]);
    }

    public static function viewEvent(Request $request, $controllerResult): ViewEvent
    {
        return new ViewEvent(
            self::httpKernelMock(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $controllerResult
        );
    }

    public static function controllerEvent(Request $request): ControllerEvent
    {
        return new ControllerEvent(
            self::httpKernelMock(),
            static function (): void {
            },
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );
    }

    public static function validator(): ValidatorInterface
    {
        return (new ValidatorBuilder())->getValidator();
    }

    public static function constraintViolationList(): ConstraintViolationListInterface
    {
        return new ConstraintViolationList([
            new ConstraintViolation('', '', [], null, '', null)
        ]);
    }

    public static function emptyConstraintViolationList(): ConstraintViolationListInterface
    {
        return new ConstraintViolationList();
    }

    public static function propertyAccessor(): PropertyAccessorInterface
    {
        return PropertyAccess::createPropertyAccessor();
    }

    public static function argumentMetadata(): ArgumentMetadata
    {
        return new ArgumentMetadata('', '', false, false, null, false);
    }

    private static function httpKernelMock(): HttpKernelInterface
    {
        return (new Generator())->getMock(HttpKernelInterface::class);
    }
}
