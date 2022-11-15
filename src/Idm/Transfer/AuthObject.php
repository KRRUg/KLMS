<?php

namespace App\Idm\Transfer;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class AuthObject
{
    /**
     * @Groups({"write"})
     * @Assert\NotBlank()
     */
    public string $name;

    /**
     * @Groups({"write"})
     * @Assert\NotBlank()
     */
    public string $secret;

    public function __construct(string $name, string $secret)
    {
        $this->name = $name;
        $this->secret = $secret;
    }
}
