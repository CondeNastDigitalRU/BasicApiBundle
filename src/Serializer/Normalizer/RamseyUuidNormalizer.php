<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Serializer\Normalizer;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class RamseyUuidNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param UuidInterface $data
     */
    public function normalize($data, string $format = null, array $context = []): string
    {
        return $data->toString();
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof UuidInterface;
    }

    public function denormalize($data, string $type, string $format = null, array $context = []): UuidInterface
    {
        return Uuid::fromString($data);
    }

    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return \is_string($data) && \is_a($type, UuidInterface::class, true) && Uuid::isValid($data);
    }
}
