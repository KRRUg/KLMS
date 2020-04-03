<?php


namespace App\Controller\Admin;


use App\Repository\NewsRepository;
use App\Service\GamerService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;


class GamerController extends AbstractController
{
    private $gamerService;

    public function __construct(UserService $userService, GamerService $gamerService)
    {
        $this->gamerService = $gamerService;
    }

    /**
     * @Route("/gamer", name="gamer")
     */
    public function index(NewsRepository $newsEntryRepository)
    {
        $gamers = $this->gamerService->getGamerWithStatus();
        return $this->render("admin/gamer/index.html.twig", [
            'gamers' => $gamers
        ]);
    }
}