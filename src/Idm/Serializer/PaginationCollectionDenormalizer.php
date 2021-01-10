<?php


namespace App\Idm\Serializer;


use App\Idm\Transfer\PaginationCollection;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

final class PaginationCollectionDenormalizer implements ContextAwareDenormalizerInterface
{
    /**
     * Specify the item type
     */
    public const ITEM_TYPE = 'item_type';

    private const FIELD_ITEMS = 'items';

    private ObjectNormalizer $normalizer;

    public function __construct(ObjectNormalizer $objectNormalizer)
    {
        $this->normalizer = $objectNormalizer;
    }

    /**
     * @inheritdoc
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        $itemType = $context[self::ITEM_TYPE];
        $content = array_map(function ($data) use ($itemType, $format, $context) {
            return $this->normalizer->denormalize($data, $itemType, $format, $context);
        }, $data['items']);
        $total = $data['total'] ?? sizeof($content);
        return new PaginationCollection($content, $total);
    }

    /**
     * @inheritdoc
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return $type === PaginationCollection::class
            && array_key_exists(self::FIELD_ITEMS, $data)
            && array_key_exists(self::ITEM_TYPE, $context)
            && !empty($context[self::ITEM_TYPE]);
    }
}