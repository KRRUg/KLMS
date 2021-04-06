<?php

namespace App\Controller\Admin;

use App\Helper\EMailRecipient;
use App\Entity\EmailSending;
use App\Entity\EMailTemplate;
use App\Form\EmailTemplateType;
use App\Repository\EMailTemplateRepository;
use App\Security\LoginUser;
use App\Service\EMailService;
use App\Service\GroupService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/email", name="email")
 */
class EMailController extends AbstractController
{
    private const CSRF_TOKEN_DELETE = "emailDeleteToken";
    private const CSRF_TOKEN_CANCEL = "emailCancelToken";

    private LoggerInterface $logger;
    private EMailService $mailService;
    private GroupService $groupService;
    private EMailTemplateRepository $templateRepository;

    public function __construct(LoggerInterface $logger, EMailService $mailService, GroupService $groupService, EMailTemplateRepository $templateRepository)
    {
        $this->logger = $logger;
        $this->mailService = $mailService;
        $this->groupService = $groupService;
        $this->templateRepository = $templateRepository;
    }

    /**
     * @Route("", name="")
     */
    public function index(Request $request)
    {
        $page = strval($request->get('page'));
        $emails = $this->templateRepository->findAll();

        return $this->render('admin/email/index.html.twig', [
            'page' => $page,
            'emails' => $emails,
            'csrf_token_cancel' => self::CSRF_TOKEN_CANCEL,
        ]);
    }

    /**
     * @Route("/new", name="_new")
     */
    public function new(Request $request)
    {
        $form = $this->createForm(EmailTemplateType::class, null, ['generate_buttons' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $template = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($template);
            $em->flush();

            if ($form->get('send')->isClicked()) {
                $this->mailService->createSending($template);
                return $this->redirectToRoute('admin_email', ['page' => 'sendings']);
            } else {
                return $this->redirectToRoute('admin_email', ['page' => 'template']);
            }
        }

        $recipient = new EMailRecipient($this->getUserFromLoginUser());

        return $this->render('admin/email/editTemplate.html.twig', [
            'form' => $form->createView(),
            'availableFields' => $recipient->getDataArray()
        ]);
    }

    private function getUserFromLoginUser()
    {
        $user = parent::getUser();
        if (!($user instanceof LoginUser)) {
            $this->logger->critical('wrong user type given (should be instance of LoginUser)');
        }

        return $user->getUser();
    }

    /**
     * @Route("/template/{id}", name="_show")
     */
    // TODO put show in modal, maybe remove this feature
    public function show(EMailTemplate $template, EMailTemplateRepository $repository)
    {
        $template = $this->mailService->renderTemplate($template, $this->getUserFromLoginUser());
        return $this->render('admin/email/show.html.twig', ['template' => $template]);
    }

    /**
     * @Route("/test/{id}", name="_send_testmail")
     */
    public function sendTestmail(EMailTemplate $template)
    {
        $this->mailService->sendByTemplate($template, $this->getUserFromLoginUser());
        $this->addFlash('success', "Test-EMail wurde an {$this->getUserFromLoginUser()->getEmail()} gesendet.");
        return $this->redirectToRoute('admin_email', ['page' => 'template']);
    }

    /**
     * @Route("/edit/{id}", name="_edit")
     */
    public function editTemplate(Request $request, EMailTemplate $template)
    {
        if ($template->wasSent()) {
            $this->addFlash('warning', 'Email wird gesended und kann nicht editiert werden.');
            return $this->redirectToRoute('admin_email', ['page' => 'sendings']);
        }

        $form = $this->createForm(EmailTemplateType::class, $template, ['generate_buttons' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $template = $form->getData();
            $em->persist($template);
            $em->flush();

            if ($form->get('send')->isClicked()) {
                $this->mailService->createSending($template);
                return $this->redirectToRoute('admin_email', ['page' => 'sendings']);
            } else {
                return $this->redirectToRoute('admin_email', ['page' => 'template']);
            }
        }

        //get available Fields
        $recipient = new EMailRecipient($this->getUserFromLoginUser());

        return $this->render('admin/email/editTemplate.html.twig', [
            'form' => $form->createView(),
            'availableFields' => $recipient->getDataArray(),
            'csrf_token_delete' => self::CSRF_TOKEN_DELETE,
        ]);
    }

    /**
     * @Route("/delete/{id}", name="_delete")
     */
    public function deleteTemplate(Request $request, EMailTemplate $template)
    {
        $token = $request->request->get('_token');
        if(!$this->isCsrfTokenValid(self::CSRF_TOKEN_DELETE, $token)) {
            $this->addFlash('error', 'The CSRF token is invalid.');
        } elseif ($this->mailService->deleteTemplate($template)) {
            $this->addFlash('success', "Erfolgreich gelöscht!");
        } else {
            $this->addFlash('error', "Konnte nicht gelöscht werden, da Sendung läuft!");
        }
        return $this->redirectToRoute('admin_email', ['page' => 'template']);
    }

    /**
     * @Route("/cancel/{id}", name="_cancel")
     */
    public function cancelEmail(Request $request, EMailTemplate $template)
    {
        $token = $request->request->get('_token');
        if(!$this->isCsrfTokenValid(self::CSRF_TOKEN_CANCEL, $token)) {
            $this->addFlash('error', 'The CSRF token is invalid.');
        } elseif ($this->mailService->cancelSending($template)) {
            $this->addFlash('success', "Erfolgreich abgebrochen!");
        } else {
            $this->addFlash('error', "Konnte nicht gelöscht werden, da schon in Sendung!");
        }
        return $this->redirectToRoute('admin_email', ['page' => 'template']);
    }
}
