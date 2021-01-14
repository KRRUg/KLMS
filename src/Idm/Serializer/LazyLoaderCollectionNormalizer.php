<?php

namespace App\Idm\Serializer;

use App\Idm\LazyLoaderCollection;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class LazyLoaderCollectionNormalizer implements NormalizerInterface
{
    public function normalize($object, $format = null, array $context = [])
    {
        return $object->uuidSet();
    }

    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof LazyLoaderCollection;
    }
}