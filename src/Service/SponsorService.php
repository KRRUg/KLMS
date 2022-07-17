<?php


namespace App\Service;


use App\Entity\Sponsor;
use App\Repository\SponsorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SponsorService
{
    private SponsorRepository $repo;
    private SettingService $settings;
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    private const SETTING_ENABLED = 'sponsor.enabled';

    /**
     * SponsorService constructor.
     * @param $repo
     * @param $settings
     * @param $em
     * @param $logger
     */
    public function __construct(SponsorRepository $repo, SettingService $settings, EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->repo = $repo;
        $this->settings = $settings;
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * @return array All content elements
     */
    public function getAll() : array
    {
        return $this->repo->findAll();
    }

    public function getRandom() : ?Sponsor
    {
        return $this->repo->findOneRandomBy();
    }

    public function count() : int
    {
        return $this->repo->count([]);
    }

    public function active() : bool
    {
        return $this->settings->get(self::SETTING_ENABLED, false);
    }

    public function activate()
    {
        $this->settings->set(self::SETTING_ENABLED, true);
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
}