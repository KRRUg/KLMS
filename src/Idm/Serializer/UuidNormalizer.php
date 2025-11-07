<?php

namespace App\Idm\Serializer;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

// This class can be removed once symfony/uid is available with symfony 5

final class UuidNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): string
    {
        return $object->toString();
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (!$this->isValid($data)) {
            throw new UnexpectedValueException('Expected a valid Uuid.');
        }

        if (null === $data) {
            return null;
        }

        return Uuid::fromString($data);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof UuidInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return Uuid::class === $type || UuidInterface::class === $type;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [UuidInterface::class => true];
    }

    private function isValid(mixed $data): bool
    {
        return $data === null || (is_string($data) && Uuid::isValid($data));
    }
}
