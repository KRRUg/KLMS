<?php

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\Teamsite;
use App\Form\TeamsiteType;
use App\Service\TeamsiteService;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/teamsite", name="teamsite")
 */
class TeamsiteController extends BaseController
{
    private LoggerInterface $logger;
    private TeamsiteService $teamsiteService;

    public function __construct(TeamsiteService $teamsiteService, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->teamsiteService = $teamsiteService;
    }

    /**
     * @Route("", name="", methods={"GET"})
     */
    public function index()
    {
        $sites = $this->teamsiteService->getAll();
        return $this->render('admin/teamsite/index.html.twig', [
            'teamsites' => $sites,
        ]);
    }

    /**
     * @Route("/edit/{id}", name="_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Teamsite $teamsite)
    {
        $array = $this->teamsiteService->renderSite($teamsite);

        $form = $this->createForm(TeamsiteType::class, $teamsite);
        $form->get('content')->setData(json_encode($array));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $array = json_decode($form->get('content')->getData(), true);
            $success = $this->teamsiteService->parseSite($teamsite, $array);
            if ($success) {
                $this->addFlash('success', 'Teamsite erfolgreich gespeichert.');
            } else {
                $this->addFlash('danger', 'Teamsite Speichern fehlgeschlagen');
            }
            return $this->redirectToRoute('admin_teamsite');
        }
        return $this->render('admin/teamsite/edit.html.twig', [
            'teamsite' => $teamsite,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/new", name="_new", methods={"GET", "POST"})
     */
    public function new(Request $request)
    {
        $teamsite = new Teamsite();

        $form = $this->createForm(TeamsiteType::class, $teamsite);
        $form->get('content')->setData(json_encode([]));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $array = json_decode($form->get('content')->getData(), true);
            $success = $this->teamsiteService->parseSite($teamsite, $array);
            if ($success) {
                $this->addFlash('success', 'Teamsite erfolgreich angelegt.');
            } else {
                $this->addFlash('danger', 'Teamsite Speichern fehlgeschlagen');
            }
            return $this->redirectToRoute('admin_teamsite');
        }
        return $this->render('admin/teamsite/edit.html.twig', [
            'teamsite' => null,
            'form' => $form->createView(),
        ]);
    }
}
