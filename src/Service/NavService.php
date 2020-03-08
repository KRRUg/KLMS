<?php


namespace App\Service;


use App\Entity\NavigationNode;
use Doctrine\ORM\EntityManagerInterface;

class NavService
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function index()
    {
        $rep = $this->em->getRepository(NavigationNode::class);
        return $rep->getRootChildren();
    }
}