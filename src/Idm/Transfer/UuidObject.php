<?php

namespace App\Idm\Transfer;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class UuidObject
{
    #[Groups(['read', 'write'])]
    #[Assert\Uuid(strict: false)]
    #[Assert\NotBlank]
    public UuidInterface $uuid;

    /**
     * UuidObject constructor.
     */
    public function __construct(UuidInterface $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    public static function fromObject(object $object): ?self
    {
        if (method_exists($object, 'getUuid')) {
            return new self($object->getUuid());
        } elseif (property_exists($object, 'uuid')) {
            return new self($object->uuid);
        }

        return null;
    }

    /**
     * @param array $array  Array to convert
     * @param bool  $strict When true, null is returned if there are additional fields (i.e. when array represents another object)
     */
    public static function fromArray(array $array, bool $strict = false): ?self
    {
        if ($strict && count($array) !== 1) {
            return null;
        }

        if (isset($array['uuid']) && Uuid::isValid($array['uuid'])) {
            return new self(Uuid::fromString($array['uuid']));
        }

        return null;
    }
}
