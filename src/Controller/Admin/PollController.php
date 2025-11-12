<?php

namespace App\Controller\Admin;

use App\Entity\Poll;
use App\Entity\PollOption;
use App\Form\PollOptionType;
use App\Form\PollType;
use App\Repository\PollOptionRepository;
use App\Repository\PollRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/poll', name: 'poll')]
#[IsGranted('ROLE_ADMIN_CONTENT')]
class PollController extends AbstractController
{
    private const DELETE_TOKEN_PREFIX = 'delete_poll_';
    private const DELETE_OPTION_TOKEN_PREFIX = 'delete_poll_option_';

    public function __construct(
        private readonly PollRepository $pollRepository,
        private readonly PollOptionRepository $pollOptionRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route(path: '', name: '', methods: ['GET'])]
    public function index(): Response
    {
        $polls = $this->pollRepository->findBy([], ['startAt' => 'DESC']);

        return $this->render('admin/poll/index.html.twig', [
            'polls' => $polls,
        ]);
    }

    #[Route(path: '/create', name: '_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $poll = new Poll();

        $form = $this->createForm(PollType::class, $poll, [
            'action' => $this->generateUrl('admin_poll_create'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($poll);
            $this->entityManager->flush();

            $this->addFlash('success', 'Umfrage gespeichert. Bitte pflege jetzt die Antwortmöglichkeiten.');

            return $this->redirectToRoute('admin_poll_options', ['id' => $poll->getId()]);
        }

        return $this->render('admin/poll/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}/edit', name: '_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Poll $poll): Response
    {
        $form = $this->createForm(PollType::class, $poll, [
            'action' => $this->generateUrl('admin_poll_edit', ['id' => $poll->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Umfrage aktualisiert.');

            return $this->redirectToRoute('admin_poll');
        }

        return $this->render('admin/poll/edit.html.twig', [
            'form' => $form->createView(),
            'poll' => $poll,
            'delete_token' => self::DELETE_TOKEN_PREFIX . $poll->getId(),
        ]);
    }

    #[Route(path: '/{id}/delete', name: '_delete', methods: ['POST'])]
    public function delete(Request $request, Poll $poll): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::DELETE_TOKEN_PREFIX . $poll->getId(), (string) $token)) {
            throw $this->createAccessDeniedException('Der CSRF Token ist ungültig.');
        }

        $this->entityManager->remove($poll);
        $this->entityManager->flush();

        $this->addFlash('success', 'Umfrage gelöscht.');

        return $this->redirectToRoute('admin_poll');
    }

    #[Route(path: '/{id}/options', name: '_options', methods: ['GET'])]
    public function options(Poll $poll): Response
    {
        $options = $this->pollOptionRepository->findBy([
            'poll' => $poll,
        ], ['position' => 'ASC', 'id' => 'ASC']);

        return $this->render('admin/poll/options.html.twig', [
            'poll' => $poll,
            'options' => $options,
            'delete_token_prefix' => self::DELETE_OPTION_TOKEN_PREFIX,
        ]);
    }

    #[Route(path: '/{id}/options/create', name: '_option_create', methods: ['GET', 'POST'])]
    public function createOption(Request $request, Poll $poll): Response
    {
        $option = new PollOption();
        $option->setPoll($poll);
        $option->setPosition($this->pollOptionRepository->count(['poll' => $poll]));

        $form = $this->createForm(PollOptionType::class, $option, [
            'action' => $this->generateUrl('admin_poll_option_create', ['id' => $poll->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($option);
            $this->entityManager->flush();

            $this->addFlash('success', 'Antwort gespeichert.');

            return $this->redirectToRoute('admin_poll_options', ['id' => $poll->getId()]);
        }

        return $this->render('admin/poll/option_form.html.twig', [
            'form' => $form->createView(),
            'poll' => $poll,
        ]);
    }

    #[Route(path: '/options/{id}/edit', name: '_option_edit', methods: ['GET', 'POST'])]
    public function editOption(Request $request, PollOption $option): Response
    {
        $form = $this->createForm(PollOptionType::class, $option, [
            'action' => $this->generateUrl('admin_poll_option_edit', ['id' => $option->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Antwort aktualisiert.');

            return $this->redirectToRoute('admin_poll_options', ['id' => $option->getPoll()->getId()]);
        }

        return $this->render('admin/poll/option_form.html.twig', [
            'form' => $form->createView(),
            'poll' => $option->getPoll(),
            'option' => $option,
        ]);
    }

    #[Route(path: '/options/{id}/delete', name: '_option_delete', methods: ['POST'])]
    public function deleteOption(Request $request, PollOption $option): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::DELETE_OPTION_TOKEN_PREFIX . $option->getId(), (string) $token)) {
            throw $this->createAccessDeniedException('Der CSRF Token ist ungültig.');
        }

        $pollId = $option->getPoll()->getId();

        $this->entityManager->remove($option);
        $this->entityManager->flush();

        $this->addFlash('success', 'Antwort gelöscht.');

        return $this->redirectToRoute('admin_poll_options', ['id' => $pollId]);
    }
}
