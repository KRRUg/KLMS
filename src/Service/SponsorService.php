<?php


namespace App\Service;


use App\Entity\Sponsor;
use App\Entity\SponsorCategory;
use App\Repository\SponsorCategoryRepository;
use App\Repository\SponsorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SponsorService
{
    private SponsorRepository $sponsorRepository;
    private SponsorCategoryRepository $categoryRepository;
    private SettingService $settings;
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    private const SETTING_ENABLED = 'sponsor.enabled';

    /**
     * SponsorService constructor.
     * @param SponsorRepository $sponsorRepository
     * @param SponsorCategoryRepository $categoryRepository
     * @param SettingService $settings
     * @param EntityManagerInterface $em
     * @param LoggerInterface $logger
     */
    public function __construct(
        SponsorRepository $sponsorRepository,
        SponsorCategoryRepository $categoryRepository,
        SettingService $settings,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ){
        $this->sponsorRepository = $sponsorRepository;
        $this->categoryRepository = $categoryRepository;
        $this->settings = $settings;
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * @return array All content elements
     */
    public function getAll() : array
    {
        return $this->sponsorRepository->findAll();
    }

    public function getRandom() : ?Sponsor
    {
        return $this->sponsorRepository->findOneRandomBy();
    }

    public function count() : int
    {
        return $this->sponsorRepository->count([]);
    }

    public function active() : bool
    {
        return $this->settings->get(self::SETTING_ENABLED, false);
    }

    public function activate()
    {
        $this->settings->set(self::SETTING_ENABLED, true);
    }

    public function hasCategories() : bool
    {
        return $this->categoryRepository->count([]) > 0;
    }

    public function delete(Sponsor $sponsor)
    {
        $this->logger->info("Deleted Sponsor {$sponsor->getId()} ({$sponsor->getName()})");
        $this->em->remove($sponsor);
        $this->em->flush();
    }

    public function save(Sponsor $sponsor)
    {
        $this->logger->info("Create or Update Sponsor {$sponsor->getId()} ({$sponsor->getName()})");
        $this->em->persist($sponsor);
        $this->em->flush();
    }

    public function getCategories()
    {
        return $this->categoryRepository->findAll();
    }

    public function renderCategories(): array
    {
        $categories = $this->categoryRepository->findAll();
        return self::render($categories);
    }

    public function parseCategories(?array $input): bool
    {
        if (!self::check($input))
            return false;

        $this->em->beginTransaction();
        $ids = [];
        $categories = $this->categoryRepository->findAll();
        $categories = array_combine(array_map(function($c) {return $c->getId(); }, $categories), $categories);

        // add new categories and update existing
        foreach ($input as $index => $a) {
            if (isset($a[self::ARRAY_ID]) && isset($categories[$a[self::ARRAY_ID]])) {
                $ids[$a[self::ARRAY_ID]] = true;
                $c = $categories[$a[self::ARRAY_ID]];
            } else {
                $c = new SponsorCategory();
            }
            $this->em->persist(
                $c
                    ->setName($a[self::ARRAY_NAME])
                    ->setPriority($index)
            );
        }
        // remove non-existing
        foreach ($categories as $category) {
            $id = $category->getId();
            if (!array_key_exists($id, $ids)) {
                $this->em->remove($category);
            }
        }
        try{
            $this->em->flush();
            $this->em->commit();
        } catch (\Exception $exception) {
            $this->em->rollback();
            return false;
        }
        return true;
    }

    private const ARRAY_ID = 'id';
    private const ARRAY_NAME = 'name';
    private const ARRAY_CAN_DELETE = 'can_delete';

    // mandatory items for submission
    private const ARRAY_ITEMS = [
        self::ARRAY_NAME,
    ];

    /**
     * @param SponsorCategory[] $cats
     * @return array
     */
    private static function render(array $cats): array
    {
        $result = array();
        usort($cats, function ($a, $b) { return $a->getPriority() - $b->getPriority(); });
        foreach ($cats as $cat) {
            $result[] = [
                self::ARRAY_ID => $cat->getId(),
                self::ARRAY_NAME => $cat->getName(),
                self::ARRAY_CAN_DELETE => $cat->getSponsors()->count() == 0,
            ];
        }
        return $result;
    }

    private static function check(array $array): bool
    {
        foreach ($array as $item) {
            foreach (self::ARRAY_ITEMS as $key) {
                if (!array_key_exists($key, $item)) {
                    return false;
                }
            }
            if (isset($item[self::ARRAY_ID]) && !is_int($item[self::ARRAY_ID])) {
                return false;
            }
        }
        return true;
    }
}
