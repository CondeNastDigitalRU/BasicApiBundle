<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait ApiEventSubscriberTrait
{
    private function canRequestHaveBody(Request $request): bool
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

    private function isApiRequest(Request $request): bool
    {
        return true === $request->attributes->get(self::ATTRIBUTE_API);
    }

    private function isJsonContentTypeRequest(Request $request): bool
    {
        return 'json' === $request->getContentType();
    }

    private function isRequestDeserializationEnabled(Request $request): bool
    {
        return true === $request->attributes->get(self::ATTRIBUTE_DESERIALIZE);
    }

    private function getRequestDeserializationType(Request $request): string
    {
        return $request->attributes->get(self::ATTRIBUTE_DESERIALIZATION_TYPE);
    }

    private function getRequestDeserializationContext(Request $request): array
    {
        return $request->attributes->get(self::ATTRIBUTE_DESERIALIZATION_CONTEXT) ?? [];
    }

    private function isRequestValidationEnabled(Request $request): bool
    {
        return true === $request->attributes->get(self::ATTRIBUTE_VALIDATE);
    }

    private function getRequestValidationGroups(Request $request): array
    {
        return $request->attributes->get(self::ATTRIBUTE_VALIDATION_GROUPS) ?? [];
    }

    private function isRequestValidationGroupSequence(Request $request): bool
    {
        return true === $request->attributes->get(self::ATTRIBUTE_VALIDATION_GROUP_SEQUENCE);
    }

    /**
     * @return mixed
     */
    private function getControllerArgument(Request $request)
    {
        return $request->attributes->get($request->attributes->get(self::ATTRIBUTE_CONTROLLER_ARGUMENT));
    }

    /**
     * @param mixed $value
     */
    private function setControllerArgument(Request $request, $value): void
    {
        $request->attributes->set($request->attributes->get(self::ATTRIBUTE_CONTROLLER_ARGUMENT), $value);
    }

    private function getResponseSerializationContext(Request $request): array
    {
        return $request->attributes->get(self::ATTRIBUTE_SERIALIZATION_CONTEXT) ?? [];
    }

    private function resetResponseSerializationContext(Request $request): void
    {
        $request->attributes->set(self::ATTRIBUTE_SERIALIZATION_CONTEXT, []);
    }

    private function getResponseStatusCode(Request $request): int
    {
        return $request->attributes->get(self::ATTRIBUTE_STATUS_CODE) ?? Response::HTTP_OK;
    }
}
