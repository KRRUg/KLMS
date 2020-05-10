<?php

namespace App\Transfer;

use Symfony\Component\Validator\Constraints as Assert;
use App\Model\ClanModel;

final class ClanEditTransfer
{

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max="250")
     * @var string
     */
    public $name;

    /**
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
     * @var array
     */
    public $admins;


    public static function fromClan(ClanModel $clan): self
    {
        $transfer = new self();
        $transfer->name = $clan->getName();
        $transfer->joinPassword = $clan->getJoinPassword();
        $transfer->website = $clan->getWebsite();
        $transfer->clantag = $clan->getClantag();
        $transfer->description = $clan->getDescription();

        $admins = [];

        foreach($clan->getUsers() as $user) {
            if($user->getAdmin()) {
                $admins[] = $user->getUser()->getUuid();
            }
        }

        if(count($admins) > 0) {
            $transfer->admins = $admins;
        }

        return $transfer;
    }
}
