<?php

namespace App\Controller\Site;

use App\Entity\Tourney;
use App\Entity\TourneyGame;
use App\Service\TourneyService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    private function generateFormRegistrationCreate(): FormInterface
    {
        $formFactory = $this->get('form.factory');
        $fb = $formFactory->createNamedBuilder('form_create');
        return $fb
            ->add('id', HiddenType::class, ['required' => true])
            ->add('create', HiddenType::class, ['required' => true, 'mapped' => false])
            ->add('name', TextType::class, ['label' => 'Name', 'required' => true])
            ->add('submit', SubmitType::class, ['label' => 'Erstellen'])
            ->getForm();
    }

    private function generateFormRegistrationJoin(): FormInterface
    {
        $formFactory = $this->get('form.factory');
        $fb = $formFactory->createNamedBuilder('form_join');

        $fun = function (FormInterface $form, mixed $data): void {
            if (is_null($data) || !isset($data['id']) || !is_numeric($data['id']))
                return;
            $tourney = $this->service->getTourneyWithTeams(intval($data['id']));
            if (is_null($tourney))
                return;
            $form
                ->remove('team')
                ->add('team', ChoiceType::class, [
                    'label' => 'Team', 'required' => true, 'multiple' => false,
                    'choices' => $tourney->getTeams(),
                    'choice_label' => 'name',
                    'choice_value' => 'id',
                    'choice_attr' => (fn ($team) => $team->countUsers() >= $tourney->getTeamsize() ? ['disabled' => 'disabled'] : []),
                ]);
        };

        return $fb
            ->add('id', HiddenType::class, ['required' => true])
            ->add('join', HiddenType::class, ['required' => true, 'mapped' => false])
            ->add('submit', SubmitType::class, ['label' => 'Beitreten'])
            ->add('team', ChoiceType::class)
            ->addEventListener(FormEvents::POST_SET_DATA, fn(FormEvent $event) => $fun($event->getForm(), $event->getForm()->getData()))
            ->addEventListener(FormEvents::PRE_SUBMIT, fn(FormEvent $event) => $fun($event->getForm(), $event->getData()))
            ->getForm();
    }

    private function generateFormRegistrationSinglePlayer(): FormInterface
    {
        $formFactory = $this->get('form.factory');
        $fb = $formFactory->createNamedBuilder('form_sp');
        return $fb
            ->add('id', HiddenType::class, ['required' => true])
            ->add('sp', HiddenType::class, ['required' => true, 'mapped' => false])
            ->add('submit', SubmitType::class, ['label' => 'Teilnehmen'])
            ->getForm();
    }

    #[Route(path: '/tourney', name: 'tourney')]
    public function index(Request $request): Response
    {
        foreach (array($this->generateFormRegistrationSinglePlayer(),
                       $this->generateFormRegistrationCreate(),
                       $this->generateFormRegistrationJoin(),
                 ) as $k => $form) {
            if (!$request->request->has($form->getName()))
                continue;
            $form->handleRequest($request);
            if (!$form->isSubmitted() || !$form->isValid())
                continue;

            $this->addFlash('warning','submitted form '.$form->getName());
        }

        $tourneys = $this->service->getVisibleTourneys();

        if (($user = $this->getUser())) {
            $user = $user->getUser();

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
        } else {
            return $this->render('site/tourney/index.html.twig', [
                'tourneys' => $tourneys,
            ]);
        }
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