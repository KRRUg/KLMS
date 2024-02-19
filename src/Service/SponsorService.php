<?php

namespace App\Service;

use App\Entity\Sponsor;
use App\Entity\SponsorCategory;
use App\Repository\SponsorCategoryRepository;
use App\Repository\SponsorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Psr\Log\LoggerInterface;

class SponsorService extends OptimalService implements WipeInterface
{
    private readonly SponsorRepository $sponsorRepository;
    private readonly SponsorCategoryRepository $categoryRepository;
    private readonly SettingService $settingService;
    private readonly EntityManagerInterface $em;
    private readonly LoggerInterface $logger;

    private const DEFAULT_CATEGORY_NAME = "Sponsoren";

    /**
     * SponsorService constructor.
     */
    public function __construct(
        SponsorRepository $sponsorRepository,
        SponsorCategoryRepository $categoryRepository,
        SettingService $settings,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        parent::__construct($settings);
        $this->sponsorRepository = $sponsorRepository;
        $this->categoryRepository = $categoryRepository;
        $this->settingService = $settings;
        $this->em = $em;
        $this->logger = $logger;
    }

    protected static function getSettingKey(): string
    {
        return 'sponsor.enabled';
    }

    protected function setUp(): void
    {
        // Create one category when the service is enabled
        if ($this->countCategories() == 0) {
            $c = new SponsorCategory();
            $this->em->persist(
                $c
                    ->setName(self::DEFAULT_CATEGORY_NAME)
                    ->setPriority(1)
            );
            $this->em->flush();
        }
    }

    /**
     * @return array All content elements
     */
    public function getAll(): array
    {
        return $this->sponsorRepository->findAll();
    }

    public function getRandom(): ?Sponsor
    {
        return $this->sponsorRepository->findOneRandomBy();
    }

    public function count(): int
    {
        return $this->sponsorRepository->count([]);
    }

    public function hasCategories(): bool
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

    /**
     * @return SponsorCategory[] categories in correct order
     */
    public function getCategories(): array
    {
        $categories = $this->categoryRepository->findAll();
        usort($categories, fn ($a, $b) => $a->getPriority() - $b->getPriority());

        return $categories;
    }

    public function countCategories(): int
    {
        return $this->categoryRepository->count([]);
    }

    public function renderCategories(): array
    {
        return self::render($this->getCategories());
    }

    public function parseCategories(?array $input): bool
    {
        if (!self::check($input)) {
            return false;
        }

        $this->em->beginTransaction();
        $ids = [];
        $categories = $this->categoryRepository->findAll();
        $categories = array_combine(array_map(fn ($c) => $c->getId(), $categories), $categories);

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
        try {
            $this->em->flush();
            $this->em->commit();
        } catch (Exception) {
            $this->em->rollback();

            return false;
        }

        return true;
    }

    private const ARRAY_ID = 'id';
    private const ARRAY_NAME = 'name';
    private const ARRAY_COUNT = 'count';

    // mandatory items for submission
    private const ARRAY_ITEMS = [
        self::ARRAY_NAME,
    ];

    /**
     * @param SponsorCategory[] $cats
     */
    private static function render(array $cats): array
    {
        $result = [];
        foreach ($cats as $cat) {
            $result[] = [
                self::ARRAY_ID => $cat->getId(),
                self::ARRAY_NAME => $cat->getName(),
                self::ARRAY_COUNT => $cat->getSponsors()->count(),
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

    public function wipe(): void
    {
        foreach ($this->sponsorRepository->findAll() as $sponsor) {
            $this->em->remove($sponsor);
        }
        foreach ($this->categoryRepository->findAll() as $category) {
            $this->em->remove($category);
        }
        $this->em->flush();
        $this->settingService->clearStartWith('sponsor');
        $this->deactivate();
    }

    public function wipeBefore(): array
    {
        return [];
    }
}
