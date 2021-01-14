<?php

namespace App\Idm\Transfer;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class UuidObject
{
    /**
     * @var UuidInterface
     *
     * @Assert\Uuid(strict=false)
     * @Assert\NotBlank()
     */
    public UuidInterface $uuid;

    /**
     * UuidObject constructor.
     * @param UuidInterface $uuid
     */
    public function __construct(UuidInterface $uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @return UuidInterface
     */
    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    public static function fromArray(array $array)
    {
        if (isset($array['uuid']) && Uuid::isValid($array['uuid']))
            return new self(Uuid::fromString($array['uuid']));

        return null;
    }
}