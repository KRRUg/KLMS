<?php

namespace App\Controller\Admin;

use App\Entity\EMail\EMailRecipient;
use App\Entity\EMail\EmailSending;
use App\Entity\EMail\EMailTemplate;
use App\Form\EMailSendingType;
use App\Form\EmailTemplateType;
use App\Repository\EMail\EmailSendingRepository;
use App\Repository\EMail\EMailTemplateRepository;
use App\Service\EMailService;
use DateTime;
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
	 * @param EMailTemplateRepository $templateRepository
	 *
	 * @param EmailSendingRepository $sendingRepository
	 *
	 * @return Response
	 */
	public function index(EMailTemplateRepository $templateRepository, EmailSendingRepository $sendingRepository)
	{
		$templates = $templateRepository->findAllTemplatesWithoutSendings();
		//$templates = $templateRepository->findAllByRole($this->getUser());
		$sendings = $sendingRepository->findAll();
		$applicationHookTemplates = $templateRepository->findAllWithApplicationHook();
		return $this->render('admin/email/index.html.twig', ['templates' => $templates, 'sendings' => $sendings, 'applicationHooks' => $applicationHookTemplates]);
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
	 * @Route("/email/sending/{id}/send/", name="email_sending_send")
	 * @param EMailService $mailService
	 * @param EmailSending $sending
	 *
	 * @return RedirectResponse|Response
	 */
	public function createSendingTasks(EMailService $mailService, EmailSending $sending)
	{
		$mailService->createSendingTasks($sending);
		//TODO Usergruppe empfangen
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

	/**
	 * @Route("/email/sending/delete/{id}", name="email_sending_delete")
	 * @param EmailSending $sending
	 * @param EMailService $mailService
	 *
	 * @return RedirectResponse
	 */
	public function deleteSending(EmailSending $sending, EMailService $mailService)
	{
		$mailService->deleteSending($sending);
		return $this->redirectToRoute('admin_email');
	}


	/**
	 * @Route("/email/sending/new/{id}", name="email_sending_new")
	 * @param EMailTemplate $template
	 * @param EMailService $mailService
	 * @param Request $request
	 *
	 * @return void
	 */
	public function newSending(EMailTemplate $template, EMailService $mailService, Request $request)
	{
		$mailService->createSending($template, null);
		return $this->redirectToRoute('admin_email');
	}

	/**
	 * @Route("/email/sending/unpublish/{id}", name="email_sending_unpublish")
	 * @param EmailSending $sending
	 * @param Request $request
	 *
	 * @return RedirectResponse
	 */
	public function unPublishSending(EmailSending $sending, Request $request)
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
	 * @Route("/email/sending/publish/{id}", name="email_sending_publish")
	 * @param EmailSending $sending
	 * @param Request $request
	 *
	 * @return RedirectResponse
	 * @throws \Exception
	 */
	public function publishSending(EmailSending $sending, Request $request)
	{
		$now = new DateTime();
		if ($sending->getIsPublishable()) {
			$sending->setIsPublished(true);
			$sending->setStatus('Freigabe erteilt');

			if ($sending->getStartTime() == null || $sending->getStartTime() < $now) {
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
	 * @Route("/email/sending/edit/{id}", name="email_sending_edit")
	 * @param EmailSending $sending
	 * @param Request $request
	 *
	 * @return RedirectResponse|Response
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
		return $this->render('admin/email/editSending.html.twig', ["form" => $form->createView()]);
	}
}
