<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface ApiEventSubscriberInterface extends EventSubscriberInterface
{
    public const ATTRIBUTE_API = '_basic_api';

    public const ATTRIBUTE_SERIALIZATION_CONTEXT = '_basic_api_serialization_context';
    public const ATTRIBUTE_SERIALIZATION_TYPE = '_basic_api_serialization_type';
    public const ATTRIBUTE_STATUS_CODE = '_basic_api_response_status_code';

    public const ATTRIBUTE_DESERIALIZE = '_basic_api_deserialize';
    public const ATTRIBUTE_DESERIALIZATION_CONTEXT = '_basic_api_deserialization_context';
    public const ATTRIBUTE_DESERIALIZATION_TYPE = '_basic_api_deserialization_type';
    public const ATTRIBUTE_CONTROLLER_ARGUMENT = '_basic_api_controller_argument';

    public const ATTRIBUTE_VALIDATE = '_basic_api_validate';
    public const ATTRIBUTE_VALIDATION_GROUPS = '_basic_api_request_validation_groups';
    public const ATTRIBUTE_VALIDATION_GROUP_SEQUENCE = '_basic_api_request_validation_group_sequence';
}
