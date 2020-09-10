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
    public function getByContent(Content $content) : array
    {
        $nodes =  $this->nodeRepo->findAllContent();
        $ret = array();
        foreach ($nodes as $node) {
            if ($node->getContent() === $content)
                $ret[] = $node;
        }
        return $ret;
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

    public function getAll()
    {
        return $this->navRepo->findAll();
    }

    public function renderNav($name = null): ?array
    {
        if (empty($name))
            return null;
        $nav = $this->navRepo->findOneByName($name);
        if (empty($nav))
            return null;
        return $this->render($nav);
    }

    public function delete(Navigation $nav)
    {
        $this->em->remove($nav);
        $this->em->flush();
    }
}