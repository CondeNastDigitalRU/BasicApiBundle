<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\DependencyInjection\Compiler;

use Condenast\BasicApiBundle\ApiDoc\ApiRouteDescriber;
use Condenast\BasicApiBundle\ApiDoc\ConstraintViolationListModelDescriber;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class NelmioApiDocPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasExtension('nelmio_api_doc')) {
            $container->setDefinition(
                'condenast_basic_api.apidoc.describer.model.constraint_violation_list',
                (new Definition(ConstraintViolationListModelDescriber::class))
                    ->addTag('nelmio_api_doc.model_describer', ['priority' => 128])
            );

            $container->setDefinition(
                'condenast_basic_api.apidoc.describer.route.api',
                (new Definition(ApiRouteDescriber::class))
                    ->setArguments([
                        new Reference('annotations.reader'),
                        new Reference('nelmio_api_doc.controller_reflector')
                    ])
                    ->addTag('nelmio_api_doc.route_describer', ['priority' => -256])
            );
        }
    }
}
