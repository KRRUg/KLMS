<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class NavigationNodeTeamsite extends NavigationNode
{
    #[ORM\ManyToOne(fetch: 'LAZY')]
    #[ORM\JoinColumn(name: 'teamsite_id', referencedColumnName: 'id')]
    private ?Teamsite $teamsite;

    public function __construct(Teamsite $teamsite = null)
    {
        parent::__construct();
        $this->teamsite = $teamsite;
    }

    public function getTeamsite(): ?Teamsite
    {
        return $this->teamsite;
    }

    public function setTeamsite(Teamsite $teamsite): self
    {
        $this->teamsite = $teamsite;

        return $this;
    }

    public function getType(): ?string
    {
        return self::NAV_NODE_TYPE_TEAMSITE;
    }

    public function getTargetId(): ?int
    {
        return $this->teamsite->getId();
    }
}