<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle;

use Condenast\BasicApiBundle\DependencyInjection\Compiler\NelmioApiDocPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class CondenastBasicApiBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new NelmioApiDocPass());
    }
}
