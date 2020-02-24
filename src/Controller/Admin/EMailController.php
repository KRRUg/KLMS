<?php

namespace App\Controller\Admin;

use App\Entity\Admin\EMail\EMailTemplate;
use App\Form\EmailTemplateCreateType;
use App\Repository\Admin\EMail\EMailTemplateRepository;
use App\Service\EMailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
     * @Route("/admin/email/{id}", name="admin_email_show")
     * @param EMailTemplate $template
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(EMailTemplate $template)
    {
        return $this->render('admin/email/show.html.twig', [
            'template' => $template
        ]);

    }

    /**
     * @Route("/admin/email/send/{id}", name="admin_email_send")
     * @param EMailTemplate $template
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function send(EMailTemplate $template, EMailService $mailService)
    {
        $mailService->sendEMail($template, 'mrandibilbao@gmail.com');
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
