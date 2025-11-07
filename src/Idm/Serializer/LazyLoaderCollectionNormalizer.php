<?php

namespace App\Idm\Serializer;

use App\Idm\LazyLoaderCollection;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class LazyLoaderCollectionNormalizer implements NormalizerInterface
{
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        return $object->toUuidArray();
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof LazyLoaderCollection;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [LazyLoaderCollection::class => true];
    }
}
