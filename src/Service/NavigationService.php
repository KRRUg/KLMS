<?php


namespace App\Service;

use App\Entity\Content;
use App\Entity\Navigation;
use App\Entity\NavigationNode;
use App\Entity\NavigationNodeContent;
use App\Entity\NavigationNodeEmpty;
use App\Entity\NavigationNodeGeneric;
use App\Repository\NavigationNodeRepository;
use App\Repository\NavigationRepository;
use Doctrine\ORM\EntityManagerInterface;

class NavigationService
{
    private $em;
    private $nodeRepo;
    private $navRepo;

    public function __construct(EntityManagerInterface $em, NavigationRepository $navRepo, NavigationNodeRepository $nodeRepo)
    {
        $this->em = $em;
        $this->navRepo = $navRepo;
        $this->nodeRepo = $nodeRepo;
    }

    /**
     * @param Content $content The content object to check
     * @return array All NavigationNode Items that refere to this content
     */
    public function getByContent(Content $c) : array
    {
        $nodes =  $this->nodeRepo->findAllContent();
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
        return [
            'id' => $node->getId(),
            'name' => $node->getName(),
            'path' => $node->getPath(),
            'type' => $node->getType(),
            'target' => $node->getTargetId(),
            'children' => $children
        ];
    }

    /**
     * @param Navigation $navigation
     * @return array rendered Array
     */
    private function render(Navigation $navigation)
    {
        $nodes = $navigation->getNodes()->toArray();
        return $this->render_rek($nodes);
    }

    /**
     * @param NavigationNode[] $nodes
     * @return array
     */
    private function render_rek(array &$nodes) : array {
        $n = array_shift($nodes);
        $rslt = [
            'name' => $n->getName(),
            'path' => $n->getPath(),
            'children' => [],
        ];

        while (!empty($nodes) && $n->getRgt() > $nodes[0]->getRgt()) {
            $rslt['children'][] = $this->render_rek($nodes);
        }

        return $rslt;
    }

    public function getNav($name = null): ?array
    {
        if (empty($name))
            return null;
        $nav = $this->navRepo->findOneByName($name);
        if (empty($nav))
            return null;
        return $this->render($nav);
    }
}