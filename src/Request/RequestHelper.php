<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Request;

use Condenast\BasicApiBundle\EventListener\ApiEventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestHelper
{
    public static function canRequestHaveBody(Request $request): bool
    {
        return \in_array(
            $request->getMethod(),
            [
                Request::METHOD_POST,
                Request::METHOD_PUT,
                Request::METHOD_PATCH,
                Request::METHOD_DELETE,
            ],
            true
        );
    }

    public static function isApiRequest(Request $request): bool
    {
        return $request->attributes->get(ApiEventSubscriberInterface::ATTRIBUTE_API) === true;
    }

    public static function isJsonContentTypeRequest(Request $request): bool
    {
        return $request->getContentType() === 'json';
    }

    public static function isRequestDeserializationEnabled(Request $request): bool
    {
        return $request->attributes->get(ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZE) === true;
    }

    public static function getRequestDeserializationType(Request $request): string
    {
        return $request->attributes->get(ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZATION_TYPE);
    }

    public static function getRequestDeserializationContext(Request $request): array
    {
        return $request->attributes->get(ApiEventSubscriberInterface::ATTRIBUTE_DESERIALIZATION_CONTEXT) ?? [];
    }

    public static function isRequestValidationEnabled(Request $request): bool
    {
        return $request->attributes->get(ApiEventSubscriberInterface::ATTRIBUTE_VALIDATE) === true;
    }

    public static function getRequestValidationGroups(Request $request): array
    {
        return $request->attributes->get(ApiEventSubscriberInterface::ATTRIBUTE_VALIDATION_GROUPS) ?? [];
    }

    /**
     * @return mixed
     */
    public static function getControllerArgument(Request $request)
    {
        return $request->attributes->get($request->attributes->get(ApiEventSubscriberInterface::ATTRIBUTE_CONTROLLER_ARGUMENT));
    }

    /**
     * @param mixed $value
     */
    public static function setControllerArgument(Request $request, $value): void
    {
        $request->attributes->set($request->attributes->get(ApiEventSubscriberInterface::ATTRIBUTE_CONTROLLER_ARGUMENT), $value);
    }

    public static function getResponseSerializationContext(Request $request): array
    {
        return $request->attributes->get(ApiEventSubscriberInterface::ATTRIBUTE_SERIALIZATION_CONTEXT) ?? [];
    }

    public static function resetResponseSerializationContext(Request $request): void
    {
        $request->attributes->set(ApiEventSubscriberInterface::ATTRIBUTE_SERIALIZATION_CONTEXT, []);
    }

    public static function getResponseStatusCode(Request $request): int
    {
        return $request->attributes->get(ApiEventSubscriberInterface::ATTRIBUTE_STATUS_CODE) ?? Response::HTTP_OK;
    }
}
