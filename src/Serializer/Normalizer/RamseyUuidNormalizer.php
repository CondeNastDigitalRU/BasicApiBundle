<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Serializer\Normalizer;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class RamseyUuidNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function normalize($data, $format = null, array $context = []): string
    {
        return $data->toString();
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof UuidInterface;
    }

    public function denormalize($data, $type, $format = null, array $context = []): UuidInterface
    {
        return Uuid::fromString($data);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return \is_string($data) && \is_a($type, UuidInterface::class, true) && Uuid::isValid($data);
    }
}
