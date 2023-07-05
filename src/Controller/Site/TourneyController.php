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
        $this->settingService = $settingService;
    }

    #[Route(path: '/tourney', name: 'tourney')]
    public function index(): Response
    {
        $tourneys = $this->service->getVisibleTourneys();

        if (($user = $this->getUser())) {
            $user = $user->getUser();

            $isRegistered = $this->service->getRegisteredTourneys($user);
            if ($this->service->userMayRegister($user)) {
                $token = $this->service->calculateUserToken($user);
                $canRegister = $this->service->getRegistrableTourneys($user);
            } else {
                $token = $canRegister = null;
            }
            $pendingTourneys = array_map(fn ($g) => $g->getTourney(), $this->service->getPendingGames($user));

            return $this->render('site/tourney/index.html.twig', [
                'tourneys' => $tourneys,
                'token' => $token,
                'is_registered' => $isRegistered,
                'can_register' => $canRegister,
                'is_pending' => $pendingTourneys,
            ]);
        } else {
            return $this->render('site/tourney/index.html.twig', [
                'tourneys' => $tourneys,
            ]);
        }
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