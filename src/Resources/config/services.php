<?php declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Condenast\BasicApiBundle\EventListener\PayloadSerializationSubscriber;
use Condenast\BasicApiBundle\EventListener\RequestConfigurationSubscriber;
use Condenast\BasicApiBundle\EventListener\RequestDeserializationSubscriber;
use Condenast\BasicApiBundle\EventListener\RequestFormatSubscriber;
use Condenast\BasicApiBundle\EventListener\RequestValidationSubscriber;
use Condenast\BasicApiBundle\Request\QueryParamBagResolver;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorBuilder;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('condenast_basic_api.event_listener.request_configuration_subscriber', RequestConfigurationSubscriber::class)
            ->args([service('annotations.reader')])
            ->tag('kernel.event_subscriber')

        ->set('condenast_basic_api.event_listener.request_format_subscriber', RequestFormatSubscriber::class)
            ->tag('kernel.event_subscriber')

        ->set('condenast_basic_api.event_listener.request_deserialization_subscriber', RequestDeserializationSubscriber::class)
            ->args([service('serializer')])
            ->tag('kernel.event_subscriber')

        ->set('condenast_basic_api.event_listener.request_validation_subscriber', RequestValidationSubscriber::class)
            ->args([service('validator')])
            ->tag('kernel.event_subscriber')

        ->set('condenast_basic_api.event_listener.payload_serialization_subscriber', PayloadSerializationSubscriber::class)
            ->args([service('serializer')])
            ->tag('kernel.event_subscriber')

        ->set('condenast_basic_api.property_accessor_builder', PropertyAccessorBuilder::class)
            ->call('enableExceptionOnInvalidIndex')
            ->call('enableExceptionOnInvalidPropertyPath')

        ->set('condenast_basic_api.property_accessor', PropertyAccessor::class)
            ->factory([
                service('condenast_basic_api.property_accessor_builder'),
                'getPropertyAccessor'
            ])

        ->set('condenast_basic_api.argument_value_resolver.query_param_bag', QueryParamBagResolver::class)
            ->args([
                service('condenast_basic_api.property_accessor'),
                service('validator')
            ])
            ->tag('controller.argument_value_resolver');
};
