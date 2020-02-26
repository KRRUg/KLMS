<?php

namespace App\Controller\Admin;

use App\Entity\Admin\EMail\EMailTemplate;
use App\Entity\HelperEntities\EMailRecipient;
use App\Form\EmailTemplateCreateType;
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
     * @Route("/admin/email/testsending/{id}", name="admin_email_testsending")
     */

    public function testsending(EMailTemplate $template, EMailService $mailService)
    {
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
        return $this->render('admin/email/show.html.twig', ['template' => $template]);
    }

    /**
     * @Route("/admin/email/send/{id}", name="admin_email_send")
     * @param EMailTemplate $template
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function send(EMailTemplate $template, EMailService $mailService)
    {
        $recipient = new EMailRecipient('Andi', 'mrandibilbao@gmail.com');
        $mailService->sendSingleEmail($template, $recipient);
        return $this->render('admin/email/show.html.twig', [
            'template' => $template
        ]);

    }


    /**
     * @Route("/admin/email/new", name="admin_email_new")
     */
    public function new(Request $request)
    {
        $form = $this->createForm(EmailTemplateCreateType::class, new EMailTemplate());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $template = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($template);
            $em->flush();
            return $this->redirectToRoute('admin_email');
        }
        return $this->render('admin/email/new.html.twig', ["form" => $form->createView()]);
    }

    /**
     * @Route("/admin/email/edit/{id}", name="admin_email_edit")
     */
    public function edit(EMailTemplate $template, Request $request)
    {
        $form = $this->createForm(EmailTemplateCreateType::class, $template);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $template = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($template);
            $em->flush();
            return $this->redirectToRoute('admin_email');
        }
        return $this->render('admin/email/edit.html.twig', ["form" => $form->createView()]);
    }

    public function store(EMailTemplate $template)
    {
        $em = $this->getDoctrine()->getManager();
        $em->persist($template);
        $em->flush();
    }


}
