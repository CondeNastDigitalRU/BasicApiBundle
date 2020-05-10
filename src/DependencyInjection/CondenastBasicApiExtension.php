<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\DependencyInjection;

use Condenast\BasicApiBundle\Serializer\Normalizer\RamseyUuidNormalizer;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class CondenastBasicApiExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $this->registerSerializerServices($container);
    }

    private function registerSerializerServices(ContainerBuilder $container): void
    {
        if (\interface_exists(UuidInterface::class)) {
            $container->setDefinition(
                'condenast_basic_api.serializer.normalizer.ramsey_uuid',
                (new Definition(RamseyUuidNormalizer::class))->addTag('serializer.normalizer', ['priority' => -10])
            );
        }
    }
}
