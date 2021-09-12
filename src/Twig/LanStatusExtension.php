<?php

namespace App\Twig;

use App\Repository\UserGamerRepository;
use App\Service\SeatmapService;
use App\Service\SettingService;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Markup;

/**
 * Renders the Status in the topBar for one Gamer
 *
 * @author Firegore
 */
class LanStatusExtension extends AbstractExtension {

    private UserGamerRepository $gamerRepository;
    private TokenStorageInterface $tokenStorage;
    private RouterInterface $router;
    private SettingService $settingService;
    private SeatmapService $seatmapService;

    public function __construct(TokenStorageInterface $tokenStorage, UserGamerRepository $userGamerRepository, RouterInterface $router, SettingService $settingService, SeatmapService $seatmapService)
    {
        $this->gamerRepository = $userGamerRepository;
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
        $this->settingService = $settingService;
        $this->seatmapService = $seatmapService;
    }

    public function getFunctions() {
        return [
            new TwigFunction('lan_status_gamer_summary', [$this, 'getGamerSummary']),
        ];
    }

    public function getGamerSummary() {

        $user = $this->tokenStorage->getToken()->getUser()->getUser();
        $gamer = $this->gamerRepository->findByUser($user);

        if($gamer) {
            if($gamer->getRegistered()) {
                if($gamer->getPaid()) {
                    if($gamer->getSeats()->count() === 0) {
                        $status = "Angemeldet | Bezahlt | {$this->_getSeatmapInfo(true)}";
                    } else {
                        $seats = '';
                        foreach ($gamer->getSeats() as $index => $seat) {
                            if ($index !== 0) {
                                $seats .= ', ';
                            }
                            $seats .= "<a href=\"{$this->router->generate('seatmap', ['seat' => $seat->getId()])}\">{$this->seatmapService->getSeatName($seat)}</a>";
                        }
                        $status = 'Angemeldet | Bezahlt | ' . $seats;
                    }
                } else {

                    $status = "Angemeldet | {$this->_getPaymentInfo(true)} | {$this->_getSeatmapInfo()}";
                }
            } else {
                $status = "{$this->_getSignUpInfo(true)} | {$this->_getPaymentInfo()} | {$this->_getSeatmapInfo()}";
            }
        } else {
            $status = "{$this->_getSignUpInfo(true)} | {$this->_getPaymentInfo()} | {$this->_getSeatmapInfo()}";
        }
        return new Markup($status, 'UTF-8');
    }

    private function _getPaymentInfo(bool $hyperlink = false): string {
        if($this->settingService->isSet('lan.page.paymentinfo')) {
            try {
                if($hyperlink) {
                    $paymentinfoUrl = $this->router->generate('content', ['id' => $this->settingService->get('lan.page.paymentinfo')]);
                    return "<a href=\"{$paymentinfoUrl}\">Nicht Bezahlt</a>";
                } else {
                    return "Nicht Bezahlt";
                }
            } Catch (RouteNotFoundException $routeNotFoundException) {
                return "Nicht Bezahlt";
            }
        } else {
            return "Nicht Bezahlt";
        }
    }

    private function _getSignUpInfo(bool $hyperlink = false): string {
                if($hyperlink) {
                    return "<a href=\"{$this->router->generate('lan_signup')}\">Nicht Angemeldet</a>";
                } else {
                    return "Nicht Angemeldet";
                }
    }

    private function _getSeatmapInfo(bool $hyperlink = false): string {
        if($hyperlink) {
            return "<a href=\"{$this->router->generate('seatmap')}\">Kein Sitzplatz</a>";
        } else {
            return "Kein Sitzplatz";
        }
    }
}
