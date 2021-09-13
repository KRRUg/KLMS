<?php

namespace App\Controller\Site;

use App\Exception\GamerLifecycleException;
use App\Helper\EmailRecipient;
use App\Repository\UserGamerRepository;
use App\Service\EmailService;
use App\Service\GamerService;
use App\Service\SettingService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
 * @Route("/lan/signup", name="lan_signup")
 */
class LanSignupController extends AbstractController
{

    /**
     * @Route("", name="")
     */
    public function index(
        EmailService $emailService,
        EntityManagerInterface $entityManager,
        GamerService $gamerService,
        LoggerInterface $logger,
        Request $request,
        SettingService $settingService,
        UserGamerRepository $userGamerRepository
    )
    {
        $termsUrl = null;
        $userGamer = $userGamerRepository->findByUser($this->getUser()->getUser());

        if($settingService->isSet('lan.page.terms')) {
            try {
                $termsUrl = $this->generateUrl('content', [ 'id' => $settingService->get('lan.page.terms')]);
            } catch (RouteNotFoundException $routeNotFoundException) {
                $logger->warning('Could not generate Route for \'lan.page.terms\' (check the Settings)!');
            }
            $fb = $this->createFormBuilder()
                ->add('confirm', CheckboxType::class, [
                    'label' => false,
                    'required' => true,
                ]);
        }

        $fb->add('submit', SubmitType::class, [
            'label' => 'Anmelden',
        ]);

        $form = $fb->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $gamerService->gamerRegister($this->getUser()->getUser());
                $this->addFlash('success', 'Erfolgreich zur LAN angemeldet');
                if($settingService->isSet('site.title')) {
                    $message = "Du hast dich zu der Veranstaltung: \"{$settingService->get('site.title')}\" erfolgreich angemeldet!";
                } else {
                    $message = "Du hast dich zu der Veranstaltung erfolgreich angemeldet!";
                }
                $emailService->scheduleHook(
                    EmailService::APP_HOOK_CHANGE_NOTIFICATION,
                    EmailRecipient::fromUser($this->getUser()->getUser()), [
                        'message' => $message,
                    ]
                );

                return $this->redirectToRoute('index');

            } Catch (GamerLifecycleException $gamerLifecycleException) {
                $this->addFlash('error', 'Du bist bereits zur LAN angemeldet!');
                return $this->redirectToRoute('index');
            }
        }

        return $this->render('site/lan_signup/signup.html.twig', [
            'form' => $form->createView(),
            'termsUrl' => $termsUrl,
            'userGamer' => $userGamer,
        ]);
    }
}
