<?php

namespace App\Controller\Admin;

use App\Controller\HttpExceptionTrait;
use App\Entity\Tourney;
use App\Entity\TourneyStatus;
use App\Form\TourneyType;
use App\Service\TourneyService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/tourney', name: 'tourney')]
class TourneyController extends AbstractController
{
    private TourneyService $service;

    private const CSRF_TOKEN_ADVANCE = 'tourneyAdvanceToken';
    private const CSRF_TOKEN_DELETE = 'tourneyDeleteToken';

    use HttpExceptionTrait;

    public function __construct(TourneyService $service, LoggerInterface $logger)
    {
        $this->service = $service;
    }

    #[Route(path: '/', name: '')]
    public function index(Request $request): Response
    {
        $tourneys = $this->service->getAll();
        return $this->render('admin/tourney/index.html.twig', [
            'tourneys' => $tourneys,
        ]);
    }

    #[Route(path: '/new', name: '_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $form = $this->createForm(TourneyType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', 'Success!!!!');
            return $this->redirectToRoute('admin_tourney');
        }

        return $this->render('admin/tourney/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/edit/{id}', name: '_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tourney $tourney): Response
    {
        $form = $this->createForm(TourneyType::class, $tourney, ['create' => false]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->service->save($tourney);
            $this->addFlash('success', 'Turnier erfolgreich aktualisiert.');
            return $this->redirectToRoute('admin_tourney');
        }

        return $this->render('admin/tourney/edit.html.twig', [
            'form' => $form->createView(),
            'csrf_token_delete' => self::CSRF_TOKEN_DELETE,
        ]);
    }

    #[Route(path: '/delete/{id}', name: '_delete')]
    public function delete(Request $request, Tourney $tourney): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_DELETE, $token)) {
            throw $this->createAccessDeniedException('The CSRF token is invalid.');
        }

        $this->service->delete($tourney);
        $this->addFlash('success', 'Erfolgreich gelöscht!');

        return $this->redirectToRoute('admin_tourney');
    }

    private function advanceSeed(Request $request, Tourney $tourney): Response
    {
        // TODO implement me here

        // ignore CSRF token, as we generate another form
        return $this->redirectToRoute('admin_tourney');
    }

    private function advanceEnterResult(Request $request, Tourney $tourney): Response
    {
        // TODO implement me here

        // ignore CSRF token, as we generate another form
        return $this->redirectToRoute('admin_tourney');
    }

    private function advanceNoop(Request $request, Tourney $tourney): Response
    {
        if ($request->getMethod() !== Request::METHOD_POST) {
            throw $this->createMethodNotAllowedException([Request::METHOD_POST]);
        }
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ADVANCE, $token)) {
            throw $this->createAccessDeniedException('The CSRF token is invalid.');
        }
        $this->service->tourneyAdvance($tourney);
        $this->addFlash('success', "Tourney {$tourney->getName()} ist nun in {$tourney->getStatus()}.");
        return $this->redirectToRoute('admin_tourney');
    }

    #[Route(path: '/advance/{id}', name: '_advance')]
    public function advance(Request $request, Tourney $tourney): Response
    {
        switch ($tourney->getStatus()) {
            case TourneyStatus::Created:
                return $this->advanceNoop($request, $tourney);
            case TourneyStatus::Registration:
                if ($tourney->hasTree()) {
                    return $this->advanceSeed($request, $tourney);
                } else {
                    return $this->advanceNoop($request, $tourney);
                }
            case TourneyStatus::Running:
                if ($tourney->hasTree()) {
                    return $this->advanceNoop($request, $tourney);
                } else {
                    return $this->advanceEnterResult($request, $tourney);
                }
            case TourneyStatus::Finished:
            default:
                throw $this->createNotFoundException();
        }
    }

    #[Route(path: '/teams/{id}', name: '_teams')]
    public function teams(Request $request, Tourney $tourney): Response
    {
        // TODO implement me here
        return $this->render('admin/tourney/teams.html.twig', [
            'tourney' => $tourney,
        ]);
    }

    #[Route(path: '/games/{id}', name: '_games')]
    public function games(Request $request, Tourney $tourney): Response
    {
        // TODO implement me here
        return $this->render('admin/tourney/games.html.twig', [
            'tourney' => $tourney,
        ]);
    }
}