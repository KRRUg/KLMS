<?php

namespace App\Controller\Admin;

use App\Entity\Sponsor;
use App\Form\SponsorType;
use App\Service\SponsorService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Route("/sponsor", name="sponsor")
 * @IsGranted("ROLE_ADMIN_CONTENT")
 */
class SponsorController extends AbstractController
{
    private const CSRF_TOKEN_DELETE = "sponsorDeleteToken";
    private const CSRF_TOKEN_ACTIVATE = "sponsorActivateToken";

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
            'csrf_token_activate' => self::CSRF_TOKEN_ACTIVATE,
        ]);
    }

    /**
     * @Route("/new", name="_new")
     */
    public function new(Request $request)
    {
        if (!$this->sponsorService->active()) {
            throw $this->createNotFoundException();
        }

        if (!$this->sponsorService->hasCategories()) {
            $this->addFlash('warning', 'Keine Kategorien vorhanden.');
            return $this->redirectToRoute('admin_sponsor');
        }

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
     * @Route("/categories", name="_categories")
     */
    public function category_edit(Request $request)
    {
        if (!$this->sponsorService->active()) {
            throw $this->createNotFoundException();
        }

        $array = $this->sponsorService->renderCategories();
        $form = $this->createFormBuilder()
            ->add('categories', HiddenType::class, [
                'required' => true,
                'data' => json_encode($array),
                'constraints' => [new Assert\Json()],
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $array = json_decode($form->getData()['categories'], true);
            $success = $this->sponsorService->parseCategories($array);
            if ($success) {
                $this->addFlash('success', 'Sponsor-Kategorien erfolgreich geändert');
            } else {
                $this->addFlash('danger', 'Sponsor-Kategorien Speichern fehlgeschlagen');
            }
            return $this->redirectToRoute('admin_sponsor');
        }

        return $this->render("admin/sponsor/categories.html.twig",[
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/delete/{id}", name="_delete")
     */
    public function delete(Request $request, Sponsor $news)
    {
        if (!$this->sponsorService->active()) {
            throw $this->createNotFoundException();
        }

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
     */
    public function edit(Request $request, Sponsor $sponsor)
    {
        if (!$this->sponsorService->active()) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(SponsorType::class, $sponsor, ['edit' => true]);

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

    /**
     * @Route("/activate", name="_activate")
     */
    public function activate(Request $request)
    {
        $token = $request->request->get('_token');
        if(!$this->isCsrfTokenValid(self::CSRF_TOKEN_ACTIVATE, $token)) {
            throw $this->createAccessDeniedException('The CSRF token is invalid.');
        }

        if (!$this->sponsorService->active()) {
            $this->sponsorService->activate();
        }
        return $this->redirectToRoute("admin_sponsor");
    }
}