<?php

namespace App\Controller\Site;

use App\Form\ContactRequestType;
use App\Service\EMailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
	/**
	 * @Route("/contact", name="contact")
	 * @param Request $request
	 *
	 * @param EMailService $mailService
	 *
	 * @return Response
	 */
	public function index(Request $request, EMailService $mailService)
	{
		$form = $this->createForm(ContactRequestType::class);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {

			$mailService->sendContactEMail($form->getData());
		}
		return $this->render('site/contact/form.html.twig', ['form' => $form->createView()]);
	}
}
