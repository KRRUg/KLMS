<?php

namespace App\Controller\Admin;

use App\Entity\Admin\EMail\EmailSending;
use App\Entity\Admin\EMail\EMailTemplate;
use App\Entity\HelperEntities\EMailRecipient;
use App\Form\EMailSendingType;
use App\Form\EmailTemplateType;
use App\Helper\EntityHelper;
use App\Repository\Admin\EMail\EMailTemplateRepository;
use App\Service\EMailService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EMailController extends AbstractController
{

    /**
     * @Route("/admin/email/", name="admin_email")
     */
    public function index(EMailTemplateRepository $repository)
    {
        $templates = $repository->findAll();
        return $this->render('admin/email/index.html.twig', [
            'controller_name' => 'EMailController',
            'templates' => $templates
        ]);

    }

    /**
     * @Route("/admin/email/new", name="admin_email_new")
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
     */

    public function createSending(EMailTemplate $template, EMailService $mailService, Request $request)
    {
        $form = $this->createForm(EMailSendingType::class, new EmailSending());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sending = $form->getData();
            $mailService->createSending($sending, $template);
            $recipients = $mailService->getPossibleEmailRecipients();

            /*
            $em = $this->getDoctrine()->getManager();
            $em->persist($send);
            $em->flush();
            */
            //return $this->render('admin/email/chooseRecipients.html.twig', ['form' => $form->createView(), 'template' => $template, 'recipients' => $recipients]);
            return $this->redirectToRoute('admin_email');
        }
        $template = $mailService->previewTemplate($template);
        return $this->render('admin/email/newSending.html.twig', ["form" => $form->createView(), 'template' => $template]);

        //dd($mailService->getVariableTokens());
        //$mailService->test();
        $mailService->addRecipient();
        $mailService->addRecipient();
        $mailService->addRecipient();
        $mailService->sendAll($template);
        return $this->redirectToRoute('admin_email');
    }

    /**
     * @Route("/admin/email/{id}", name="admin_email_show")
     */
    public function show(EMailTemplate $template, EMailService $mailService)
    {
        $template = $mailService->previewTemplate($template);
        return $this->render('admin/email/show.html.twig', ['template' => $template]);
    }

    /**
     * @Route("/admin/email/send/{id}", name="admin_email_send")
     * @param EMailTemplate $template
     * @return \Symfony\Component\HttpFoundation\Response
     */
    //TODO: mit aktivem Userkonto ausführen
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
     */
    public function edit(EMailTemplate $template, Request $request)
    {
        $form = $this->createForm(EmailTemplateType::class, $template);
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

    public function store(EMailTemplate $template)
    {
        $em = $this->getDoctrine()->getManager();
        $em->persist($template);
        $em->flush();
    }


}
