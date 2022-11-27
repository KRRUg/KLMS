<?php

namespace App\Transfer;

use Symfony\Component\Validator\Constraints as Assert;

final class Error
{
    #[Assert\Type(type: 'int')]
    public $code;

    #[Assert\Type(type: 'string')]
    #[Assert\NotBlank]
    public $message;

    #[Assert\Type(type: 'string')]
    public $detail;

    public static function withMessage(string $msg): self
    {
        $ret = new self();
        $ret->message = $msg;

        return $ret;
    }

    public static function withMessageAndDetail(string $msg, string $detail): self
    {
        $ret = new self();
        $ret->message = $msg;
        $ret->detail = $detail;

        return $ret;
    }
}
