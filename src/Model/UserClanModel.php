<?php

namespace App\Model;


use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;



class UserClanModel
{
    
    /**
     * @Groups({"default2", "clanview"})
     */
    private $user;

    /**
     * @Groups("default")
     */
    private $clan;

    /**
     * @Groups({"default", "clanview"})
     */
    private $admin;
    

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getClan()
    {
        return $this->clan;
    }

    /**
     * @param mixed $clan
     */
    public function setClan($clan): void
    {
        $this->clan = $clan;
    }

    /**
     * @return mixed
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * @param mixed $admin
     */
    public function setAdmin($admin): void
    {
        $this->admin = $admin;
    }

}
