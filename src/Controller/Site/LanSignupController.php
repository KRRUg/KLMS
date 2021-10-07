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
    private GamerService $gamerService;
    private SettingService $settingService;
    private EmailService $emailService;
    private LoggerInterface $logger;

    /**
     * LanSignupController constructor.
     */
    public function __construct(GamerService $gamerService,
                                SettingService $settingService,
                                EmailService $emailService,
                                LoggerInterface $logger)
    {
        $this->gamerService = $gamerService;
        $this->settingService = $settingService;
        $this->emailService = $emailService;
        $this->logger = $logger;
    }


    /**
     * @Route("", name="")
     */
    public function index(Request $request)
    {
        if (!$this->settingService->isSet('lan.signup.enabled')) {
            return $this->redirectToRoute('index');
        }

        $user = $this->getUser()->getUser();

        if ($this->gamerService->gamerHasRegistered($user)) {
            $this->addFlash('warning', 'Du bist bereits zur Veranstaltung angemeldet!');
            return $this->redirectToRoute('index');
        }

        $fb = $this->createFormBuilder()
            ->add('confirm', CheckboxType::class, [
                'label' => "Ich melde mich zur Veranstaltung an",
                'required' => true,
            ]);

        $form = $fb->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('confirm')->getData()) {
                try {
                    $this->gamerService->gamerRegister($user);
                    $this->addFlash('success', 'Erfolgreich zur Veranstaltung angemeldet');
                    if ($this->settingService->isSet('site.title')) {
                        $message = "Du hast dich zu der Veranstaltung: \"{$this->settingService->get('site.title')}\" erfolgreich angemeldet!";
                    } else {
                        $message = "Du hast dich zu der Veranstaltung erfolgreich angemeldet!";
                    }
                    $this->emailService->scheduleHook(
                        EmailService::APP_HOOK_CHANGE_NOTIFICATION,
                        EmailRecipient::fromUser($this->getUser()->getUser()), [
                            'message' => $message,
                        ]
                    );

                    return $this->redirectToRoute('index');

                } catch (GamerLifecycleException $gamerLifecycleException) {
                    $this->addFlash('error', 'Anmeldung ist fehlgeschlagen!');
                    $this->logger->error("Gamer  registrierung ist Fehlgeschlagen.");
                }
            }
        }

        return $this->render('site/lan_signup/signup.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
