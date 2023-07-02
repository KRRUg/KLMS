<?php

namespace App\Controller\Site;

use App\Entity\Tourney;
use App\Entity\TourneyGame;
use App\Service\GamerService;
use App\Service\SettingService;
use App\Service\TourneyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TourneyController extends AbstractController
{
    private readonly TourneyService $service;
    private readonly GamerService $gamerService;
    private readonly SettingService $settingService;

    public function __construct(TourneyService $service, GamerService $gamerService, SettingService $settingService)
    {
        $this->service = $service;
        $this->gamerService = $gamerService;
        $this->settingService = $settingService;
    }

    #[Route(path: '/tourney', name: 'tourney')]
    public function index(): Response
    {
        $tourneys = $this->service->getVisibleTourneys();

        $token = 0;
        $myTourneys = [];
        $canRegister = false;

        if (($user = $this->getUser())) {
            $user = $user->getUser();
            $token = $this->service->calculateUserToken($user);
            $myTourneys = $this->service->getRegisteredTourneys($user);
            $canRegister = $this->gamerService->gamerIsOnLan($user) && $this->settingService->get('lan.tourney.registration_open');
        }

        return $this->render('site/tourney/index.html.twig', [
            'token' => $token,
            'tourneys' => $tourneys,
            'my_tourneys' => $myTourneys,
            'can_register' => $canRegister,
        ]);
    }

    #[Route(path: '/tourney/{id}', name: 'tourney_show')]
    public function byId(Tourney $tourney): Response
    {
        $final = TourneyService::getFinal($tourney);
        $array = [[$final]];
        $level = 0;
        $next = true;
        while ($next) {
            $array[] = [];
            $next = false;
            /** @var TourneyGame $game */
            foreach ($array[$level++] as $game) {
                $next = $next || !is_null($game);
                $array[$level][] = is_null($game) ? null : $game->getChild(true);
                $array[$level][] = is_null($game) ? null : $game->getChild(false);
            }
        }
        array_pop($array);
        array_pop($array);
        $array = array_reverse($array);

        return $this->render('site/tourney/show.html.twig', [
            'tourney' => $tourney,
            'tree' => $array,
        ]);
    }
}