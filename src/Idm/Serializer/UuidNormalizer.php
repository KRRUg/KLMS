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
     * @inheritdoc
     */
    public function normalize($object, $format = null, array $context = array())
    {
        return $object->toString();
    }

    /**
     * @inheritdoc
     */
    public function denormalize($data, $type, $format = null, array $context = [])
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
     * @inheritdoc
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof UuidInterface;
    }

    /**
     * @inheritdoc
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return (Uuid::class === $type || UuidInterface::class === $type);
    }

    private function isValid($data)
    {
        return $data === null || (is_string($data) && Uuid::isValid($data));
    }
}