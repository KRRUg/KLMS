<?php

namespace App\Controller\Admin;

use App\Helper\EMailRecipient;
use App\Entity\EmailSending;
use App\Entity\EMailTemplate;
use App\Form\EMailSendingType;
use App\Form\EmailTemplateType;
use App\Repository\EmailSendingRepository;
use App\Repository\EMailTemplateRepository;
use App\Security\LoginUser;
use App\Service\EMailService;
use App\Service\GroupService;
use DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/email", name="email")
 */
class EMailController extends AbstractController
{
    private LoggerInterface $logger;
    private EMailService $mailService;
    private GroupService $groupService;

    public function __construct(LoggerInterface $logger, EMailService $mailService, GroupService $groupService)
    {
        $this->logger = $logger;
        $this->mailService = $mailService;
        $this->groupService = $groupService;
    }

    /**
     * @Route("", name="")
     */
    public function index(Request $request, EMailTemplateRepository $templateRepository, EmailSendingRepository $sendingRepository)
    {
        $page = strval($request->get('page'));
        $templates = $templateRepository->findAllTemplatesWithoutSendings();
        $sendings = $sendingRepository->findAll();

        return $this->render('admin/email/index.html.twig', [
            'page' => $page,
            'templates' => $templates,
            'sendings' => $sendings
        ]);
    }

    /**
     * @Route("/new", name="_new")
     */
    public function new(Request $request)
    {
        $form = $this->createForm(EmailTemplateType::class, new EMailTemplate());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $template = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($template);
            $em->flush();

            return $this->redirectToRoute('admin_email');
        }

        $recipient = new EMailRecipient($this->getUserFromLoginUser());

        return $this->render('admin/email/editTemplate.html.twig', ['form' => $form->createView(), 'availableFields' => $recipient->getDataArray()]);
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
     * @Route("/sending/{id}/send/", name="_sending_send")
     */
    public function createSendingTasks(EmailSending $sending)
    {
        $this->mailService->createSendingTasks($sending);
        //TODO Usergruppe empfangen
        return $this->redirectToRoute('admin_email');
    }

    /**
     * @Route("/template/{id}", name="_show")
     */
    // TODO put show in modal, maybe remove this feature
    public function show(EMailTemplate $template, EMailTemplateRepository $repository)
    {
        // TODO fix this check
//        if (!$repository->hasTemplateAccess($this->getUserFromLoginUser(), $template)) {// TODO durch AccessDeniedHandler ersetzten sobald verfügbar
//            return $this->redirectToRoute('admin_email');
//        }

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
    public function editTemplate(EMailTemplate $template, Request $request, EMailTemplateRepository $repository)
    {
        // TODO fix access checks
//        if (!$repository->hasTemplateAccess($this->getUserFromLoginUser(), $template)) { // TODO durch AccessDeniedHandler ersetzten sobald verfügbar
//            $this->addFlash('warning', 'Keine Rechte für Applikations-E-Mails');
//
//            return $this->redirectToRoute('admin_email');
//        }
        $form = $this->createForm(EmailTemplateType::class, $template);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $template = $form->getData();
            $em->persist($template);
            $em->flush();

            return $this->redirectToRoute('admin_email');
        }

        //get available Fields
        $recipient = new EMailRecipient($this->getUserFromLoginUser());

        return $this->render('admin/email/editTemplate.html.twig', ['form' => $form->createView(), 'availableFields' => $recipient->getDataArray()]);
    }

    /**
     * @Route("/template/delete/{id}", name="_delete")
     */
    // TODO add CSRF protection here
    public function deleteTemplate(EMailTemplate $template)
    {
        $this->mailService->deleteTemplate($template);

        return $this->redirectToRoute('admin_email');
    }

    /**
     * @Route("/sending/delete/{id}", name="_sending_delete")
     */
    // TODO add CSRF protection here
    public function deleteSending(EmailSending $sending)
    {
        $this->mailService->deleteSending($sending);

        return $this->redirectToRoute('admin_email');
    }

    /**
     * @Route("/sending/new/{id}", name="_sending_new")
     */
    public function newSending(EMailTemplate $template)
    {
        $this->mailService->createSending($template, null);

        return $this->redirectToRoute('admin_email');
    }

    /**
     * @Route("/sending/unpublish/{id}", name="_sending_unpublish")
     */
    public function unPublishSending(EmailSending $sending)
    {
        if ($sending->getIsUnpublishable()) {
            $sending->setIsPublished(false);
            $sending->setStatus('Freigabe zuzrückgezogen');
            $em = $this->getDoctrine()->getManager();
            $em->persist($sending);
            $em->flush();
        }

        return $this->redirectToRoute('admin_email');
    }

    /**
     * @Route("/sending/publish/{id}", name="_sending_publish")
     */
    public function publishSending(EmailSending $sending)
    {
        $now = new DateTime();
        if ($sending->getIsPublishable()) {
            $sending->setIsPublished(true);
            $sending->setStatus('Freigabe erteilt');

            if (null == $sending->getStartTime() || $sending->getStartTime() < $now) {
                $sending->setStartTime($now->modify('+15 minutes'));
                //$sending->setStatus('Zeit auf ' . date_format($now, 'd.m.Y H:i') . ' gesetzt');
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($sending);
            $em->flush();
        }

        return $this->redirectToRoute('admin_email');
    }

    /**
     * @Route("/sending/edit/{id}", name="_sending_edit")
     */
    public function editSending(EmailSending $sending, Request $request)
    {
        $form = $this->createForm(EMailSendingType::class, $sending);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $sending = $form->getData();
            $em->persist($sending);
            $em->flush();

            return $this->redirectToRoute('admin_email');
        }

        return $this->render('admin/email/editSending.html.twig', ['form' => $form->createView()]);
    }
}
