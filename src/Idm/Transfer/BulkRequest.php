<?php

namespace App\Idm\Transfer;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class BulkRequest
{
    #[Groups(['write'])]
    #[Assert\All([new Assert\NotBlank(), new Assert\Uuid(strict: false)])]
    public array $uuid = [];

    public function __construct(array $uuid)
    {
        $this->uuid = $uuid;
    }
}
