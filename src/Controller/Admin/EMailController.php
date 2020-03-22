<?php

namespace App\Controller\Admin;

use App\Entity\Admin\EMail\EmailSending;
use App\Entity\Admin\EMail\EMailTemplate;
use App\Entity\HelperEntities\EMailRecipient;
use App\Form\EMailSendingType;
use App\Form\EmailTemplateType;
use App\Repository\Admin\EMail\EMailSendingRepository;
use App\Repository\Admin\EMail\EMailTemplateRepository;
use App\Service\EMailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;

/**
 * Class EMailController
 * @package App\Controller\Admin
 */
class EMailController extends AbstractController
{

    /**
     * @Route("/admin/email/", name="admin_email")
     * @param EMailTemplateRepository $repository
     * @return Response
     */
    public function index(EMailTemplateRepository $repository)
    {
        $templates = $repository->findAll();
        return $this->render('admin/email/index.html.twig', [
            'templates' => $templates
        ]);
    }

    /**
     * @Route("/admin/email/sendings", name="admin_email_sendings")
     * @param EMailSendingRepository $repository
     * @return Response
     */
    public function sendings(EMailSendingRepository $repository)
    {
        $sendings = $repository->findAll();
        return $this->render('admin/email/sendings.html.twig', [
            'sendings' => $sendings
        ]);
    }

    /**
     * @Route("/admin/email/new", name="admin_email_new")
     * @param Request $request
     * @return RedirectResponse|Response
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
        return $this->render('admin/email/editTemplate.html.twig', ["form" => $form->createView()]);
    }

    /**
     * @Route("/admin/email/testsending/{id}", name="admin_email_testsending")
     * @param EMailTemplate $template
     * @param EMailService $mailService
     * @param Request $request
     * @return RedirectResponse|Response
     */

    public function createSending(EMailTemplate $template, EMailService $mailService, Request $request)
    {
        $form = $this->createForm(EMailSendingType::class, new EmailSending());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sending = $form->getData();
            $mailService->createSending($sending, $template);
            return $this->redirectToRoute('admin_email_sendings');
        }
        $template = $mailService->previewTemplate($template);
        return $this->render('admin/email/newSending.html.twig', ["form" => $form->createView(), 'template' => $template]);
    }

    /**
     * @Route("/admin/email/{id}", name="admin_email_show")
     * @param EMailTemplate $template
     * @param EMailService $mailService
     * @return Response
     */
    public function show(EMailTemplate $template, EMailService $mailService)
    {
        $template = $mailService->previewTemplate($template);
        return $this->render('admin/email/show.html.twig', ['template' => $template]);
    }

    /**
     * @Route("/admin/email/send/{id}", name="admin_email_send")
     * @param EMailTemplate $template
     * @param EMailService $mailService
     * @return Response
     */
    public function send(EMailTemplate $template, EMailService $mailService)
    {
        $recipient = new EMailRecipient(1, 'Andi', 'mrandibilbao@gmail.com');
        $mailService->sendSingleEmail($template, $recipient);
        return $this->render('admin/email/show.html.twig', [
            'template' => $template
        ]);

    }


    /**
     * @Route("/admin/email/edit/{id}", name="admin_email_edit")
     * @param EMailTemplate $template
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function editTemplate(EMailTemplate $template, Request $request)
    {
        $template->setIsPublished(false);

        $form = $this->createForm(EmailTemplateType::class, $template);
        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManager();
        $em->persist($template);
        $em->flush();

        if ($form->isSubmitted() && $form->isValid()) {
            $template = $form->getData();
            $em->persist($template);
            $em->flush();
            return $this->redirectToRoute('admin_email');
        }
        return $this->render('admin/email/editTemplate.html.twig', ["form" => $form->createView()]);
    }

    /**
     * @Route("/admin/email/template/delete/{id}", name="admin_email_delete")
     * @param EMailTemplate $template
     * @param EMailService $mailService
     * @return RedirectResponse
     */
    public function deleteTemplate(EMailTemplate $template, EMailService $mailService)
    {
        $mailService->deleteTemplate($template);
        return $this->redirectToRoute('admin_email');
    }

    /**
     * @Route("/admin/email/sending/delete/{id}", name="admin_email_sending_delete")
     * @param EmailSending $sending
     * @param EMailService $mailService
     * @return RedirectResponse
     */
    public function deleteSending(EmailSending $sending, EMailService $mailService)
    {
        $mailService->deleteSending($sending);
        return $this->redirectToRoute('admin_email_sendings');
    }


    /**
     * @Route("/admin/email/massSendingTest/do", name="admin_email_masssendingtest")
     * @param EMailService $mailService
     * @return RedirectResponse
     */
    public function massSendingTest(EMailService $mailService)
    {
        $mailService->sendEmailTasks();
        $mailService->repairSendingStats();
        return $this->redirectToRoute('admin_email');
    }

    /**
     * @Route("/admin/email/applicationhook/test", name="admin_email_applicationhook_test")
     * @param EMailService $mailService
     * @return RedirectResponse
     */
    public function applicationhookTest(EMailService $mailService)
    {
        $testRecipient = new EMailRecipient(1, "Bieblov", "mrandibilbao@gmail.com");
        $mailService->sendByApplicationHook("REGISTER", $testRecipient);
        return $this->redirectToRoute('admin_email');
    }

    /**
     * @Route("/admin/email/methodcall/test", name="admin_email_methodcall_test")
     * @param EMailService $mailService
     */
    public function methodCallTest(EMailService $mailService)
    {
        $testRecipient = new EMailRecipient(1, "Bieblov", "mrandibilbao@gmail.com");
        $methodName = 'getName';
        $testData = $testRecipient->{$methodName}();
        //$testData = call_user_func([$testRecipient, 'generateTestLinkHash']);
        dd($testData);
    }

}
