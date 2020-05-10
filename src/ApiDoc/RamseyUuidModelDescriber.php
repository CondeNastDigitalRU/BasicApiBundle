<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\ApiDoc;

use EXSyst\Component\Swagger\Schema;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\ModelDescriber\ModelDescriberInterface;
use Ramsey\Uuid\UuidInterface;

class RamseyUuidModelDescriber implements ModelDescriberInterface
{
    public function describe(Model $model, Schema $schema): void
    {
        $schema
            ->setType('string')
            ->setFormat('uuid')
            ->setExample('00000000-0000-0000-0000-000000000000');
    }

    public function supports(Model $model): bool
    {
        $className = $model->getType()->getClassName();
        return null !== $className && \is_a($className, UuidInterface::class, true);
    }
}
