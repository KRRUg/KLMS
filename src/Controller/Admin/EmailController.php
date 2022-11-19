<?php

namespace App\Controller\Admin;

use App\Entity\Email;
use App\Form\EmailType;
use App\Helper\EmailRecipient;
use App\Repository\EmailRepository;
use App\Security\LoginUser;
use App\Service\EmailService;
use App\Service\GroupService;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/email", name="email")
 * @IsGranted("ROLE_ADMIN_MAIL")
 */
class EmailController extends AbstractController
{
    private const CSRF_TOKEN_DELETE = 'emailDeleteToken';
    private const CSRF_TOKEN_CANCEL = 'emailCancelToken';

    private readonly LoggerInterface $logger;
    private readonly EmailService $mailService;
    private readonly GroupService $groupService;
    private readonly EmailRepository $templateRepository;

    public function __construct(LoggerInterface $logger, EmailService $mailService, GroupService $groupService, EmailRepository $templateRepository)
    {
        $this->logger = $logger;
        $this->mailService = $mailService;
        $this->groupService = $groupService;
        $this->templateRepository = $templateRepository;
    }

    /**
     * @Route("", name="")
     */
    public function index(Request $request): Response
    {
        $page = strval($request->get('page'));
        $emails = $this->templateRepository->findAll();
        $stats = [];
        foreach ($emails as $email) {
            if ($email->getEmailSending()) {
                $stats[$email->getId()] = $this->templateRepository->countMails($email);
            }
        }

        return $this->render('admin/email/index.html.twig', [
            'page' => $page,
            'emails' => $emails,
            'stats' => $stats,
            'csrf_token_cancel' => self::CSRF_TOKEN_CANCEL,
        ]);
    }

    /**
     * @Route("/new", name="_new")
     */
    public function new(Request $request): Response
    {
        $form = $this->createForm(EmailType::class, null, ['generate_buttons' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $template = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($template);
            $em->flush();

            if ($form->get('send')->isClicked()) {
                $this->mailService->scheduleSending($template);

                return $this->redirectToRoute('admin_email', ['page' => 'sendings']);
            } else {
                return $this->redirectToRoute('admin_email', ['page' => 'template']);
            }
        }

        $recipient = $this->getUserFromLoginUser();

        return $this->render('admin/email/edit.html.twig', [
            'form' => $form->createView(),
            'availableFields' => $recipient->getDataArray(),
        ]);
    }

    /**
     * @Route("/template/{id}", name="_show")
     */
    public function show(Email $template): Response
    {
        $email = $this->mailService->renderTemplate($template, $this->getUserFromLoginUser());

        return new Response($email['html']);
    }

    /**
     * @Route("/test/{id}", name="_send_testmail")
     */
    public function sendTestmail(Email $template): Response
    {
        $recipient = $this->getUserFromLoginUser();
        $success = $this->mailService->sendByTemplate($template, $recipient, false);
        if ($success) {
            $this->addFlash('success', "Test-E-Mail wurde an {$recipient->getEmailAddress()} gesendet.");
        } else {
            $this->addFlash('error', "Test-E-Mail konnte nicht an {$recipient->getEmailAddress()} gesendet werden.");
        }

        return $this->redirectToRoute('admin_email', ['page' => 'template']);
    }

    /**
     * @Route("/edit/{id}", name="_edit")
     */
    public function editTemplate(Request $request, Email $template): Response
    {
        if ($template->wasSent()) {
            $this->addFlash('warning', 'Email wird gesended und kann nicht editiert werden.');

            return $this->redirectToRoute('admin_email', ['page' => 'sendings']);
        }

        $form = $this->createForm(EmailType::class, $template, ['generate_buttons' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $template = $form->getData();
            $em->persist($template);
            $em->flush();

            if ($form->get('send')->isClicked()) {
                $this->mailService->scheduleSending($template);

                return $this->redirectToRoute('admin_email', ['page' => 'sendings']);
            } else {
                return $this->redirectToRoute('admin_email', ['page' => 'template']);
            }
        }

        // get available Fields
        $recipient = $this->getUserFromLoginUser();

        return $this->render('admin/email/edit.html.twig', [
            'form' => $form->createView(),
            'availableFields' => $recipient->getDataArray(),
            'csrf_token_delete' => self::CSRF_TOKEN_DELETE,
        ]);
    }

    /**
     * @Route("/delete/{id}", name="_delete")
     */
    public function deleteTemplate(Request $request, Email $template): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_DELETE, $token)) {
            throw $this->createAccessDeniedException('The CSRF token is invalid.');
        }

        if ($this->mailService->deleteTemplate($template)) {
            $this->addFlash('success', 'Erfolgreich gelöscht!');
        } else {
            $this->addFlash('error', 'Konnte nicht gelöscht werden, da Sendung läuft!');
        }

        return $this->redirectToRoute('admin_email', ['page' => 'template']);
    }

    /**
     * @Route("/cancel/{id}", name="_cancel")
     */
    public function cancelEmail(Request $request, Email $template): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_CANCEL, $token)) {
            throw $this->createAccessDeniedException('The CSRF token is invalid.');
        }

        if ($this->mailService->cancelSending($template)) {
            $this->addFlash('success', 'Erfolgreich abgebrochen!');
        } else {
            $this->addFlash('error', 'Konnte nicht gelöscht werden, da schon in Sendung!');
        }

        return $this->redirectToRoute('admin_email', ['page' => 'template']);
    }

    private function getUserFromLoginUser(): ?EmailRecipient
    {
        $user = parent::getUser();
        if (!($user instanceof LoginUser)) {
            $this->logger->critical('wrong user type given (should be instance of LoginUser)');

            return null;
        }

        return EmailRecipient::fromUser($user->getUser());
    }
}
