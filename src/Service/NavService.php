<?php


namespace App\Service;

use App\Entity\Content;
use App\Entity\NavigationNode;
use App\Entity\NavigationNodeContent;
use App\Entity\NavigationNodeEmpty;
use App\Entity\NavigationNodeGeneric;
use App\Repository\NavigationRepository;
use Doctrine\ORM\EntityManagerInterface;

class NavService
{
    private $em;
    private $rep;

    public function __construct(EntityManagerInterface $em, NavigationRepository $rep)
    {
        $this->em = $em;
        $this->rep = $rep;
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

    public function newNode(NavigationNode $parent, ?string $type) : ?NavigationNode
    {
        if (empty($type))
            return null;

        switch (strtoupper($type)) {
            case 'CONTENT':
                $new = new NavigationNodeContent();
                break;
            case 'PATH':
                $new = new NavigationNodeGeneric();
                break;
            case 'EMPTY':
                $new = new NavigationNodeEmpty();
                break;
            default:
                return null;
        }
        $new->setOrder(0);
        $new->setName("new");
        $parent->addChildNode($new);
        $this->em->persist($new);
        $this->em->flush();
        return $new;
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
            // start with 1 to be able to insert new nodes at position 0
            if ($n->getOrder() != $i+1) {
                $n->setOrder($i+1);
            }
            $this->fixOrder($n);
        }
        $this->em->persist($node);
    }

    /**
     * @param Content $content The content object to check
     * @return array All NavigationNode Items that refere to this content
     */
    public function getByContent(Content $c) : array
    {
        $nodes =  $this->rep->findAllContent();
        $ret = array();
        foreach ($nodes as $node) {
            if ($node->getContent() === $c)
                $ret[] = $node;
        }
        return $ret;
    }

    /**
     * @param NavigationNode $node
     * @return array Associative array of node and children recursively
     */
    public static function toArray(NavigationNode $node) : array
    {
        $children = array();
        foreach ($node->getChildNodes() as $child) {
            $children[] = self::toArray($child);
        }
        //usort($children, function ($n1, $n2) { return $n1['order'] - $n2['order']; });
        return [
            'id' => $node->getId(),
            'path' => $node->getPath(),
            //'order' => $node->getOrder(),
            'type' => $node->getType(),
            'target' => $node->getTargetId(),
            'children' => $children
        ];
    }

    public function getNav()
    {
        return $this->rep->getRoot()->getChildNodes();
    }

    public function getNavArray()
    {
        return self::toArray($this->rep->getRoot());
    }
}