<?php

namespace App\Controller\Admin;

use App\Controller\HttpExceptionTrait;
use App\Entity\Tourney;
use App\Entity\TourneyStage;
use App\Exception\ServiceException;
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

    #[Route(path: '/details/{id}', name: '_details')]
    public function details(Request $request, Tourney $tourney): Response
    {
        // TODO render either full site or partial content, depending on xhr request (here and everywhere)

        // TODO implement me
        return $this->render('admin/tourney/details.modal.html.twig', [
            'tourney' => $tourney,
            'csrf_token_advance' => self::CSRF_TOKEN_ADVANCE,
        ]);
    }

    #[Route(path: '/seed/{id}', name: '_seed')]
    public function seed(Request $request, Tourney $tourney): Response
    {
        // TODO generate form and make the seed accordingly
        $this->service->seed($tourney);
        $this->addFlash('success', 'Seed wurde neu berechnet');
        return $this->redirectToRoute('admin_tourney');
    }

    #[Route(path: '/result/{id}', name: '_result')]
    public function enterResult(Request $request, Tourney $tourney): Response
    {
        // TODO implement me here

        // ignore CSRF token, as we generate another form
        return $this->redirectToRoute('admin_tourney');
    }

    #[Route(path: '/advance/{id}', name: '_advance', methods: ['POST'])]
    public function advance(Request $request, Tourney $tourney): Response
    {
        if ($tourney->getStatus() == TourneyStage::Finished) {
            throw $this->createNotFoundException();
        }
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ADVANCE, $token)) {
            throw $this->createAccessDeniedException('The CSRF token is invalid.');
        }
        try{
            $this->service->advance($tourney);
            $this->addFlash('success', "Tourney {$tourney->getName()} {$tourney->getStatus()->getAdjective()}.");
        } catch (ServiceException $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirectToRoute('admin_tourney');
    }

    #[Route(path: '/back/{id}', name: '_back', methods: ['POST'])]
    public function back(Request $request, Tourney $tourney): Response
    {
        if ($tourney->getStatus() == TourneyStage::Created) {
            throw $this->createNotFoundException();
        }
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ADVANCE, $token)) {
            throw $this->createAccessDeniedException('The CSRF token is invalid.');
        }
        try{
            $this->service->back($tourney);
            $this->addFlash('success', "Tourney {$tourney->getName()} {$tourney->getStatus()->getAdjective()}.");
        } catch (ServiceException $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirectToRoute('admin_tourney');
    }
}