<?php

namespace App\Idm\Transfer;

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
}