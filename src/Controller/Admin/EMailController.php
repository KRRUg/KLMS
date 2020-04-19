<?php

namespace App\Controller\Admin;

use App\Entity\EMail\EMailRecipient;
use App\Entity\EMail\EMailTemplate;
use App\Form\EmailTemplateType;
use App\Repository\EMail\EMailTemplateRepository;
use App\Service\EMailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class EMailController
 * @package App\Controller\Admin
 */
class EMailController extends AbstractController
{


	/**
	 * @Route("/email/", name="email")
	 * @param EMailTemplateRepository $repository
	 *
	 * @return Response
	 */
	public function index(EMailTemplateRepository $repository)
	{
		$templates = $repository->findAllByRole($this->getUser());
		return $this->render('admin/email/index.html.twig', ['templates' => $templates]);
	}

	/**
	 * @Route("/email/new", name="email_new")
	 * @param Request $request
	 *
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

		$recipient = new EMailRecipient($this->getUser());

		return $this->render('admin/email/editTemplate.html.twig', ["form" => $form->createView(), "availableFields" => $recipient->getDataArray()]);
	}

	/**
	 * @Route("/email/template/{id}/createsendingtasks/", name="email_template_createsendingtasks")
	 * @param EMailTemplate $template
	 * @param EMailService $mailService
	 *
	 * @return RedirectResponse|Response
	 */
	public function createSendingTasks(EMailService $mailService, EMailTemplate $template)
	{
		//TODO Usergruppe empfangen
		$mailService->getPossibleEmailRecipients();
		$errors = $mailService->createSendingTasks($template);
		$mailService->sendEmailTasks($template);
		foreach ($errors as $error) {
			$this->addFlash('error', $error);
		}
		return $this->redirectToRoute('admin_email');
	}

	/**
	 * @Route("/email/template/{id}", name="email_show")
	 * @param EMailTemplate $template
	 * @param EMailService $mailService
	 *
	 * @return Response
	 */
	public function show(EMailTemplate $template, EMailService $mailService, EMailTemplateRepository $repository)
	{
		if (!$repository->hasTemplateAccess($this->getUser(), $template))// TODO durch AccessDeniedHandler ersetzten sobald verfügbar
			return $this->redirectToRoute('admin_email');

		$template = $mailService->renderTemplate($template, $this->getUser());
		return $this->render('admin/email/show.html.twig', ['template' => $template]);
	}

	/**
	 * @Route("/email/sendTestmail/{id}", name="email_send_testmail")
	 * @param EMailTemplate $template
	 * @param EMailService $mailService
	 *
	 * @return Response
	 */
	public function sendTestmail(EMailTemplate $template, EMailService $mailService)
	{
		$mailService->sendSingleEmail($template, $this->getUser());
		$template = $mailService->renderTemplate($template, $this->getUser());
		return $this->render('admin/email/show.html.twig', ['template' => $template]);
	}


	/**
	 * @Route("/email/edit/{id}", name="email_edit")
	 * @param EMailTemplate $template
	 * @param Request $request
	 *
	 * @return RedirectResponse|Response
	 */
	public function editTemplate(EMailTemplate $template, Request $request, EMailTemplateRepository $repository)
	{
		if (!$repository->hasTemplateAccess($this->getUser(), $template)) // TODO durch AccessDeniedHandler ersetzten sobald verfügbar
		{
			$this->addFlash('warning', "Keine Rechte für Applikations-E-Mails");
			return $this->redirectToRoute('admin_email');
		}
		$form = $this->createForm(EmailTemplateType::class, $template);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$em = $this->getDoctrine()->getManager();
			$template = $form->getData();
			$em->persist($template);
			$em->flush();
			return $this->redirectToRoute('admin_email');
		}

		//available Fields holen
		$recipient = new EMailRecipient($this->getUser());
		return $this->render('admin/email/editTemplate.html.twig', ["form" => $form->createView(), "availableFields" => $recipient->getDataArray()]);
	}

	/**
	 * @Route("/email/template/delete/{id}", name="email_delete")
	 * @param EMailTemplate $template
	 * @param EMailService $mailService
	 *
	 * @return RedirectResponse
	 */
	public function deleteTemplate(EMailTemplate $template, EMailService $mailService)
	{
		$mailService->deleteTemplate($template);
		return $this->redirectToRoute('admin_email');
	}
}
