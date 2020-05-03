<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\DependencyInjection\Compiler;

use Condenast\BasicApiBundle\ApiDoc\ConstraintViolationListModelDescriber;
use Condenast\BasicApiBundle\ApiDoc\RamseyUuidModelDescriber;
use Condenast\BasicApiBundle\ApiDoc\RouteDescriber;
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
                ConstraintViolationListModelDescriber::class,
                (new Definition(ConstraintViolationListModelDescriber::class))->addTag('nelmio_api_doc.model_describer', ['priority' => -10])
            );

            $container->setDefinition(
                RamseyUuidModelDescriber::class,
                (new Definition(RamseyUuidModelDescriber::class))->addTag('nelmio_api_doc.model_describer', ['priority' => -10])
            );

            $container->setDefinition(
                RouteDescriber::class,
                (new Definition(RouteDescriber::class))
                    ->setArgument('$annotationReader', new Reference('basic_api.annotations_reader'))
                    ->setArgument('$controllerReflector', new Reference('nelmio_api_doc.controller_reflector'))
                    ->addTag('nelmio_api_doc.route_describer', ['priority' => -10])
            );
        }
    }
}
