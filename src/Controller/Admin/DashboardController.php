<?php

namespace App\Controller\Admin;

use App\Entity\Clan;
use App\Entity\ShopOrderStatus;
use App\Entity\User;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Repository\ShopOrderPositionRepository;
use App\Repository\ShopOrderRepository;
use App\Service\StatisticService;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    private readonly IdmRepository $userRepo;
    private readonly IdmRepository $clanRepo;

    public function __construct(
        private readonly StatisticService $statisticService,
        private readonly ShopOrderRepository $orderRepo,
        private readonly ShopOrderPositionRepository $positionRepo,
        IdmManager $idmManager,
    ) {
        $this->userRepo = $idmManager->getRepository(User::class);
        $this->clanRepo = $idmManager->getRepository(Clan::class);
    }

    #[Route(path: '/', name: 'dashboard')]
    public function index(): Response
    {
        $weekAgo = new DateTime('-7 days');
        $monthStart = new DateTime('first day of this month midnight');

        // Community Statistiken (IDM-managed — kein lokaler DB-Zugriff möglich)
        $totalUsersCount = $this->userRepo->findAll()->count();
        $allClans = $this->clanRepo->findAll();
        $totalClans = $allClans->count();
        $openClans = count(array_filter(iterator_to_array($allClans), fn (Clan $c) => empty($c->getJoinPassword())));

        // Tickets
        $totalTickets = $this->statisticService->getTicketsTotal();
        $ticketsSoldCount = $this->statisticService->countSoldTickets();
        $ticketsAvailable = $totalTickets - $ticketsSoldCount;
        $ticketPercentage = $totalTickets > 0 ? round(($ticketsSoldCount / $totalTickets) * 100) : 0;

        // Shop
        $pendingOrdersCount = $this->orderRepo->countOrders(status: ShopOrderStatus::Created);
        $revenueWeek = $this->orderRepo->sumRevenue(ShopOrderStatus::Paid, $weekAgo) / 100.0;
        $revenueMonth = $this->orderRepo->sumRevenue(ShopOrderStatus::Paid, $monthStart) / 100.0;
        $topItems = $this->positionRepo->topAddonItems(5);

        return $this->render('admin/dashboard/index.html.twig', [
            'communityStats' => [
                'totalUsers' => $totalUsersCount,
                'clansTotal' => $totalClans,
                'clansOpen' => $openClans,
            ],
            'shopStats' => [
                'ticketsTotal' => $totalTickets,
                'ticketsSold' => $ticketsSoldCount,
                'ticketsAvailable' => $ticketsAvailable,
                'ticketPercentage' => $ticketPercentage,
                'pendingOrders' => $pendingOrdersCount,
                'revenueWeek' => $revenueWeek,
                'revenueMonth' => $revenueMonth,
                'topItems' => $topItems,
            ],
        ]);
    }
}
