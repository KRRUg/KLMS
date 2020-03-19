<?php


namespace App\Service;

use App\Entity\NavigationNode;
use Doctrine\ORM\EntityManagerInterface;

class NavService
{
    private $em;
    private $rep;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->rep = $em->getRepository(NavigationNode::class);
    }

    private function swapOrder(NavigationNode $n1, NavigationNode $n2)
    {
        $tmp = $n1->getOrder();
        $n1->setOrder($n2->getOrder());
        $n2->setOrder($tmp);
        $this->em->persist($n1);
        $this->em->persist($n2);
        $this->em->flush();
    }

    public function moveNode(NavigationNode $node, bool $up)
    {
        $ofs = $up ? -1 : 1;
        $parent = $node->getParent();
        $this->fixOrder($parent);
        $nodes = $parent->getChildNodes();
        foreach ($nodes as $n) {
            if ($n->getOrder() == $node->getOrder() + $ofs) {
                $this->swapOrder($n, $node);
                return true;
            }
        }
        return false;
    }

    public function removeNode(NavigationNode $node)
    {
        $parent = $node->getParent();
        $this->removeNodeR($node);
        $this->em->refresh($parent);
        $this->fixOrder($parent);
        $this->em->flush();
        return true;
    }

    private function removeNodeR(NavigationNode $node)
    {
        $children = $node->getChildNodes();
        foreach ($children as $child) {
            $this->removeNode($child);
        }
        $this->em->remove($node);
    }

    private function fixOrder(NavigationNode $node)
    {
        $nodes = $node->getChildNodes();
        for ($i = 0; $i < count($nodes); $i += 1) {
            $n = $nodes[$i];
            if ($n->getOrder() != $i) {
                $n->setOrder($i);
            }
            $this->fixOrder($n);
        }
        $this->em->persist($node);
    }

    public function getNav()
    {
        return $this->rep->getRootChildren();
    }
}