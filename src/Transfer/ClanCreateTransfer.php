<?php

namespace App\Transfer;

use Symfony\Component\Validator\Constraints as Assert;

final class ClanCreateTransfer
{

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max="250")
     * @var string
     */
    public $name;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max="250")
     * @var string
     */
    public $joinPassword;

    /**
     * @Assert\Url()
     * @Assert\Length(max="250")
     * @var string
     */
    public $website;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max="24")
     * @var string
     */
    public $clantag;

    /**
     * @Assert\Length(max="250")
     * @var string
     */
    public $description;

    /**
     * @Assert\Length(max="250")
     * @var string
     */
    public $user;
}
