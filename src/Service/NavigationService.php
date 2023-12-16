<?php

namespace App\Service;

use App\Entity\Content;
use App\Entity\Navigation;
use App\Entity\NavigationNode;
use App\Entity\NavigationNodeContent;
use App\Entity\NavigationNodeEmpty;
use App\Entity\NavigationNodeGeneric;
use App\Entity\NavigationNodeRoot;
use App\Entity\NavigationNodeTeamsite;
use App\Entity\Teamsite;
use App\Repository\NavigationNodeRepository;
use App\Repository\NavigationRepository;
use Doctrine\ORM\EntityManagerInterface;

class NavigationService implements Resettable
{
    private readonly EntityManagerInterface $em;
    private readonly NavigationRepository $navRepo;
    private readonly NavigationNodeRepository $nodeRepo;

    final public const URL_REGEX = '(^(?:http(s)?:\/\/)?[\w.-]+(?:\.[\w\.-]+)+[\w\-\._~:\/?#[\]@!\$&\'\(\)\*\+,;=.]+$|^(\/[\w\-\._~:\/?#[\]@!\$&\'\(\)\*\+,;=.]*)+$)';

    final public const NAV_LOCATION_MAIN = 'main_menu';
    final public const NAV_LOCATION_FOOTER = 'footer';

    final public const NAV_LOCATION_KEYS = [
        self::NAV_LOCATION_MAIN,
        self::NAV_LOCATION_FOOTER,
    ];

    final public const NAV_LOCATION_DEPTHS = [
        self::NAV_LOCATION_MAIN => 2,
        self::NAV_LOCATION_FOOTER => 1,
    ];

    public function __construct(
        EntityManagerInterface $em,
        NavigationRepository $navRepo,
        NavigationNodeRepository $nodeRepo
    ) {
        $this->em = $em;
        $this->navRepo = $navRepo;
        $this->nodeRepo = $nodeRepo;
    }

    /**
     * @param Content $content The content object to check
     *
     * @return array All NavigationNode Items that refer to this content
     */
    public function getByContent(Content $content): array
    {
        $nodes = $this->nodeRepo->findAllContent();
        $ret = [];
        foreach ($nodes as $node) {
            if ($node->getContent() === $content) {
                $ret[] = $node;
            }
        }

        return $ret;
    }

    private const ARRAY_NAME = 'name';
    private const ARRAY_PATH = 'path';
    private const ARRAY_CHILD = 'children';
    private const ARRAY_TYPE = 'type';
    private const ARRAY_ITEMS = [
        self::ARRAY_NAME,
        self::ARRAY_PATH,
        self::ARRAY_CHILD,
        self::ARRAY_TYPE,
    ];

    /**
     * @param NavigationNode[] $nodes
     */
    private static function render(array &$nodes): array
    {
        $n = array_shift($nodes);
        $rslt = [
            self::ARRAY_NAME => $n->getName(),
            self::ARRAY_PATH => $n->getPath(),
            self::ARRAY_TYPE => $n->getType(),
            self::ARRAY_CHILD => [],
        ];

        while (!empty($nodes) && $n->getRgt() > $nodes[0]->getRgt()) {
            $rslt[self::ARRAY_CHILD][] = self::render($nodes);
        }

        return $rslt;
    }

    private static function check(array &$a): bool
    {
        foreach (self::ARRAY_ITEMS as $item) {
            if (!array_key_exists($item, $a)) {
                return false;
            }
        }
        if (!(is_array($a[self::ARRAY_CHILD])
            && (is_null($a[self::ARRAY_PATH]) || is_string($a[self::ARRAY_PATH]))
            && is_string($a[self::ARRAY_NAME])
        )) {
            return false;
        }
        foreach ($a[self::ARRAY_CHILD] as &$child) {
            if (!self::check($child)) {
                return false;
            }
        }

        return true;
    }

    private function guessType(?string $type, ?string $path): ?NavigationNode
    {
        switch ($type) {
            case NavigationNode::NAV_NODE_TYPE_CONTENT:
                if (preg_match('/^\/?content\/(\d+)\/?$/', $path, $output_array)) {
                    $content = $this->em->getRepository(Content::class)->findById(intval($output_array[1]));

                    return new NavigationNodeContent($content);
                }
                break;
            case NavigationNode::NAV_NODE_TYPE_TEAMSITE:
                if (preg_match('/^\/?teamsite\/(\d+)\/?$/', $path, $output_array)) {
                    $teamsite = $this->em->getRepository(Teamsite::class)->findById(intval($output_array[1]));

                    return new NavigationNodeTeamsite($teamsite);
                }
                break;
            case NavigationNode::NAV_NODE_TYPE_PATH:
                $pattern = '/'.self::URL_REGEX.'/';
                if (preg_match($pattern, $path)) {
                    return new NavigationNodeGeneric($path);
                }
                break;
            case NavigationNode::NAV_NODE_TYPE_EMPTY:
                return new NavigationNodeEmpty();
            default:
                return null;
        }

        return null;
    }

    /**
     * @param array $parse  Array to parse. No checks performed, run self::check for syntactic check first.
     * @param array $result Reference to the result array. Must be [] to generate a root element.
     * @param int   $count  the lft value to start with
     *
     * @return bool True if parsing was successful, false otherwise
     */
    private function parse(array $parse, array &$result, int &$count = 1): bool
    {
        $path = $parse[self::ARRAY_PATH];
        $name = $parse[self::ARRAY_NAME];
        $type = $parse[self::ARRAY_TYPE];
        $children = $parse[self::ARRAY_CHILD];

        if (empty($result)) {
            $node = new NavigationNodeRoot();
        } else {
            $node = $this->guessType($type, $path);
        }

        if (empty($node)) {
            return false;
        }

        $node->setName($name);
        $node->setLft($count++);
        $result[] = $node;
        $valid = true;
        foreach ($children as $child) {
            $valid &= $this->parse($child, $result, $count);
        }
        $node->setRgt($count++);

        return $valid;
    }

    public function getAll(): array
    {
        $navs = $this->navRepo->findByNames(self::NAV_LOCATION_KEYS);
        $names = array_map(fn ($nav) => $nav->getName(), $navs);
        foreach (self::NAV_LOCATION_KEYS as $key) {
            if (!in_array($key, $names)) {
                $navs[] = $this->createNav(
                    $key,
                    array_key_exists($key, self::NAV_LOCATION_DEPTHS) ? self::NAV_LOCATION_DEPTHS[$key] : null);
            }
        }

        return $navs;
    }

    protected function createNav(string $name, ?int $max_depth = null): Navigation
    {
        $new = new Navigation();
        $new->setName($name);
        $new->addNode((new NavigationNodeRoot())->setName($name)->setPos(1, 2));
        $new->setMaxDepth($max_depth);
        $this->em->persist($new);
        $this->em->flush();
        $this->em->refresh($new);

        return $new;
    }

    public function renderNavByName(string $name): ?array
    {
        if (empty($name)) {
            return null;
        }
        $nav = $this->navRepo->findOneByName($name);
        if (empty($nav)) {
            return null;
        }

        return $this->renderNav($nav);
    }

    public function renderNav(Navigation $nav): ?array
    {
        $nodes = $nav->getNodes()->toArray();

        return self::render($nodes);
    }

    /**
     * @param ?array $input Array structure to parse
     */
    public function parseNav(Navigation $nav, ?array $input): bool
    {
        $result = [];

        if (empty($input)) {
            return false;
        }
        if (!self::check($input)) {
            return false;
        }
        if (!$this->parse($input, $result)) {
            return false;
        }

        $this->em->beginTransaction();
        $nav->clearNodes();
        $this->em->persist($nav);
        $this->em->flush();

        foreach ($result as $item) {
            $nav->addNode($item);
        }
        $this->em->flush();
        $this->em->commit();

        return true;
    }

    public function delete(Navigation $nav): void
    {
        $this->em->remove($nav);
        $this->em->flush();
    }

    public function reset(): void
    {
        foreach ($this->navRepo->findAll() as $nav) {
            $this->em->remove($nav);
        }
        $this->em->flush();
    }

    public function resetBefore(): array
    {
        return [];
    }
}
