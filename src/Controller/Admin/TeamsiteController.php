<?php

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\Teamsite;
use App\Form\TeamsiteType;
use App\Service\TeamsiteService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/teamsite', name: 'teamsite')]
class TeamsiteController extends BaseController
{
    private readonly LoggerInterface $logger;
    private readonly TeamsiteService $teamsiteService;

    public function __construct(TeamsiteService $teamsiteService, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->teamsiteService = $teamsiteService;
    }

    #[Route(path: '', name: '', methods: ['GET'])]
    public function index(): Response
    {
        $sites = $this->teamsiteService->getAll();

        return $this->render('admin/teamsite/index.html.twig', [
            'teamsites' => $sites,
        ]);
    }

    #[Route(path: '/edit/{id}', name: '_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Teamsite $teamsite): Response
    {
        $array = $this->teamsiteService->renderSite($teamsite);

        $form = $this->createForm(TeamsiteType::class, $teamsite);
        $form->get('content')->setData(json_encode($array, JSON_THROW_ON_ERROR));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $array = json_decode((string) $form->get('content')->getData(), true, 512, JSON_THROW_ON_ERROR);
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

    #[Route(path: '/new', name: '_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $teamsite = new Teamsite();

        $form = $this->createForm(TeamsiteType::class, $teamsite);
        $form->get('content')->setData(json_encode([]));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $array = json_decode((string) $form->get('content')->getData(), true, 512, JSON_THROW_ON_ERROR);
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
