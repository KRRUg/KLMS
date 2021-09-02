<?php

namespace App\Controller\Site;


use App\Service\SeatmapService;
use App\Service\SettingService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/seatmap", name="seatmap")
 */
class SeatmapController extends AbstractController
{
    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        SettingService $settingService,
        SeatmapService $seatmapService
    )
    {
        $this->em =$entityManager;
        $this->logger = $logger;
        $this->settingService = $settingService;
        $this->seatmapService = $seatmapService;
    }

    /**
     * @Route("", name="")
     */
    public function index()
    {


        return $this->render('site/seatmap/index.html.twig', [
            'seatmap' => $this->seatmapService->getSeatmap(),
        ]);
    }
}
