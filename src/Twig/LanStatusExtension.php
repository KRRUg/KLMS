<?php

namespace App\Twig;

use App\Repository\UserGamerRepository;
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

    public function __construct(TokenStorageInterface $tokenStorage, UserGamerRepository $userGamerRepository, RouterInterface $router, SettingService $settingService)
    {
        $this->gamerRepository = $userGamerRepository;
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
        $this->settingService = $settingService;
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
                if($gamer->getPayed()) {
                    if($gamer->getSeats()->count() === 0) {
                        $status = "Angemeldet | Bezahlt | Kein Sitzplatz";
                    } else {
                        $seats = '';
                        foreach ($gamer->getSeats() as $index => $seat) {
                            if ($index !== 0) {
                                $seats .= ', ';
                            }
                            $seats .= "<a href=\"{$this->router->generate('index')}\">{$seat->getName()}</a>";
                        }
                        $status = 'Angemeldet | Bezahlt | ' . $seats;
                    }
                } else {

                    $status = "Angemeldet | {$this->_getPaymentInfo(true)} | Kein Sitzplatz";
                }
            } else {
                $status = "{$this->_getSignUpInfo(true)} | {$this->_getPaymentInfo()} | Kein Sitzplatz";
            }
        } else {
            $status = "{$this->_getSignUpInfo(true)} | {$this->_getPaymentInfo()} | Kein Sitzplatz";
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
            //FIXME
            return "<a href=\"{$this->router->generate('lan_signup')}\">Nicht Angemeldet</a>";
        } else {
            return "Nicht Angemeldet";
        }
    }
}
