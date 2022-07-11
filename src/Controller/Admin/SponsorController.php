<?php

namespace App\Controller\Admin;

use App\Entity\Sponsor;
use App\Form\SponsorType;
use App\Service\SponsorService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/sponsor", name="sponsor")
 * @IsGranted("ROLE_ADMIN_CONTENT")
 */
class SponsorController extends AbstractController
{
    private const CSRF_TOKEN_DELETE = "sponsorDeleteToken";

    private SponsorService $sponsorService;

    public function __construct(SponsorService $service)
    {
        $this->sponsorService = $service;
    }

    /**
     * @Route("/", name="", methods={"GET"})
     */
    public function index()
    {
        $sponsors = $this->sponsorService->getAll();
        return $this->render('admin/sponsor/index.html.twig', [
            'sponsors' => $sponsors,
        ]);
    }

    /**
     * @Route("/new", name="_new")
     */
    public function new(Request $request) {
        $form = $this->createForm(SponsorType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->sponsorService->save($form->getData());
            return $this->redirectToRoute("admin_sponsor");
        }

        return $this->render("admin/sponsor/edit.html.twig", [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/delete/{id}", name="_delete")
     * @ParamConverter()
     */
    public function delete(Request $request, Sponsor $news) {
        $token = $request->request->get('_token');
        if(!$this->isCsrfTokenValid(self::CSRF_TOKEN_DELETE, $token)) {
            throw $this->createAccessDeniedException('The CSRF token is invalid.');
        }

        $this->sponsorService->delete($news);
        $this->addFlash('success', "Erfolgreich gelöscht!");
        return $this->redirectToRoute("admin_sponsor");
    }

    /**
     * @Route("/edit/{id}", name="_edit")
     * @ParamConverter()
     */
    public function edit(Request $request, Sponsor $news) {
        $form = $this->createForm(SponsorType::class, $news);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->sponsorService->save($form->getData());
            return $this->redirectToRoute("admin_sponsor");
        }

        return $this->render("admin/sponsor/edit.html.twig", [
            'form' => $form->createView(),
            'csrf_token_delete' => self::CSRF_TOKEN_DELETE,
        ]);
    }
}