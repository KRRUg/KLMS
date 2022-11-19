<?php

namespace App\Idm\Transfer;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class BulkRequest
{
    /**
     * @Assert\All({
     *      @Assert\NotBlank,
     *      @Assert\Uuid(strict=false)
     * })
     */
    #[Groups(['write'])]
    public array $uuid = [];

    public function __construct(array $uuid)
    {
        $this->uuid = $uuid;
    }
}
