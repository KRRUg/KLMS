<?php

namespace App\Controller\Site;

use App\Entity\Tourney;
use App\Entity\TourneyGame;
use App\Exception\ServiceException;
use App\Service\TourneyService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class TourneyController extends AbstractController
{
    private readonly TourneyService $service;
    private readonly UserService $userService;

    public function __construct(TourneyService $service, UserService $userService)
    {
        $this->service = $service;
        $this->userService = $userService;
    }

    private function createNamedFormBuilder(string $name): FormBuilderInterface
    {
        return $this->container->get('form.factory')->createNamedBuilder($name);
    }

    // TODO move this somewhere global
    private function createBadRequestHttpException(string $message = 'Bad request', \Throwable $previous = null): BadRequestHttpException
    {
        return new BadRequestHttpException($message, $previous);
    }

    private function getTourneyOfId($id): ?Tourney
    {
        if (is_numeric($id))
            return $this->service->getTourneyWithTeams(intval($id));
        return null;
    }

    private function generateFormRegistrationCreate(): FormInterface
    {
        return $this->createNamedFormBuilder('form_create')
            ->add('id', HiddenType::class, ['required' => true])
            ->add('name', TextType::class, ['label' => 'Name', 'required' => true])
            ->add('submit', SubmitType::class, ['label' => 'Erstellen'])
            ->getForm();
    }

    private function generateFormRegistrationJoin(): FormInterface
    {
        $fun = function (FormEvent $event): void {
            $data = $event->getForm()->getData() ?? $event->getData();
            if (!is_array($data) || !isset($data['id']))
                return;
            $tourney = $this->getTourneyOfId($data['id']);
            if (is_null($tourney))
                return;
            $event->getForm()
                ->remove('team')
                ->add('team', ChoiceType::class, [
                    'label' => 'Team', 'required' => true, 'multiple' => false,
                    'choices' => $tourney->getTeams(),
                    'choice_label' => 'name',
                    'choice_value' => 'id',
                    'choice_attr' => (fn ($team) => $team->countUsers() >= $tourney->getTeamsize() ? ['disabled' => 'disabled'] : []),
                ]);
        };

        return $this->createNamedFormBuilder('form_join')
            ->add('id', HiddenType::class, ['required' => true])
            ->add('submit', SubmitType::class, ['label' => 'Beitreten'])
            ->add('team', ChoiceType::class)
            ->addEventListener(FormEvents::POST_SET_DATA, $fun)
            ->addEventListener(FormEvents::PRE_SUBMIT, $fun)
            ->getForm();
    }

    private function generateFormRegistrationSinglePlayer(): FormInterface
    {
        return $this->createNamedFormBuilder('form_sp')
            ->add('id', HiddenType::class, ['required' => true])
            ->add('submit', SubmitType::class, ['label' => 'Teilnehmen'])
            ->getForm();
    }

    private function handleRegistrationForm(Request $request, FormInterface $form, callable $getTeam): void
    {
        $user = ($u = $this->getUser()) ? $u->getUser() : null;
        if (is_null($user)) {
            return;
        }
        if (!$request->request->has($form->getName())) {
            return;
        }
        $form->handleRequest($request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            return;
        }

        $tourney = $this->getTourneyOfId($form->get('id')->getData());
        $team = $getTeam($form);
        if (is_null($tourney)) {
            throw $this->createBadRequestHttpException();
        }

        try {
            $this->service->userRegister($tourney, $user, $team);
        } catch (ServiceException $e) {
            $this->addFlash('error',
                'Fehler beim Anmelden: ' .
                match ($e->getCause()) {
                    ServiceException::CAUSE_IN_USE => 'Turnier registrierung ist nicht (mehr) offen.',
                    ServiceException::CAUSE_EXIST => 'User ist bereits registriert.',
                    ServiceException::CAUSE_EMPTY => 'User hat nicht genug Token',
                    default => 'unbekannter Fehler.'
                }
            );
        }
    }

    #[Route(path: '/tourney', name: 'tourney')]
    public function index(Request $request): Response
    {
        $user = ($u = $this->getUser()) ? $u->getUser() : null;
        $tourneys = $this->service->getVisibleTourneys();

        if (is_null($user)) {
            return $this->render('site/tourney/index.html.twig', [
                'tourneys' => $tourneys,
            ]);
        }

        $this->handleRegistrationForm($request, $this->generateFormRegistrationSinglePlayer(), fn ($form) => null);
        $this->handleRegistrationForm($request, $this->generateFormRegistrationCreate(), fn ($form) => $form->get('name')->getData());
        $this->handleRegistrationForm($request, $this->generateFormRegistrationJoin(), fn ($form) => $form->get('team')->getData());

        $form_create = array();
        $form_join = array();
        $token = $canRegister = null;

        $isRegistered = $this->service->getRegisteredTourneys($user);
        if ($this->service->userMayRegister($user)) {
            $token = $this->service->calculateUserToken($user);
            $canRegister = $this->service->getRegistrableTourneys($user);

            foreach ($canRegister as $t) {
                if ($t->isSinglePlayer()) {
                    $form_create[$t->getId()] = $this->generateFormRegistrationSinglePlayer()->setData(['id' => $t->getId()])->createView();
                    $form_join[$t->getId()] = null;
                } else {
                    $form_create[$t->getId()] = $this->generateFormRegistrationCreate()->setData(['id' => $t->getId()])->createView();
                    $form_join[$t->getId()] = $this->generateFormRegistrationJoin()->setData(['id' => $t->getId()])->createView();
                }
            }
        }
        $pendingTourneys = array_map(fn ($g) => $g->getTourney(), $this->service->getPendingGames($user));

        return $this->render('site/tourney/index.html.twig', [
            'tourneys' => $tourneys,
            'token' => $token,
            'is_registered' => $isRegistered,
            'can_register' => $canRegister,
            'is_pending' => $pendingTourneys,
            'form_create' => $form_create,
            'form_join' => $form_join,
        ]);
    }

    #[Route(path: '/tourney/{id}', name: 'tourney_show')]
    public function byId(int $id): Response
    {
        $tourney = $this->service->getTourneyWithTeams($id);
        if (is_null($tourney)) {
            throw new NotFoundHttpException();
        }

        $gamers = $this->service->getAllUsersOfTourney($tourney);
        $this->userService->preloadUsers($gamers);

        $final = TourneyService::getFinal($tourney);
        $array = [[$final]];
        $level = 0;
        $next = true;
        while ($next) {
            $array[] = [];
            $next = false;
            /** @var TourneyGame $game */
            foreach ($array[$level++] as $game) {
                $next = $next || !is_null($game);
                $array[$level][] = is_null($game) ? null : $game->getChild(true);
                $array[$level][] = is_null($game) ? null : $game->getChild(false);
            }
        }
        array_pop($array);
        array_pop($array);
        $array = array_reverse($array);

        return $this->render('site/tourney/show.html.twig', [
            'tourney' => $tourney,
            'tree' => $array,
        ]);
    }
}