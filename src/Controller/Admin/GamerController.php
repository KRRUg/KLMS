<?php


namespace App\Controller\Admin;

use App\Exception\UserServiceException;
use App\Service\GamerService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/gamer", name="gamer")
 * @IsGranted("ROLE_ADMIN_ADMISSION")
 */
class GamerController extends AbstractController
{
    private $gamerService;
    private $userService;

    public function __construct(UserService $userService, GamerService $gamerService)
    {
        $this->userService = $userService;
        $this->gamerService = $gamerService;
    }

    /**
     * @Route("/", name="")
     */
    public function index(Request $request)
    {
        $gamers = $this->gamerService->getRegisteredGamer();
        return $this->render("admin/gamer/index.html.twig", [
            'gamers' => $gamers
        ]);
    }
}