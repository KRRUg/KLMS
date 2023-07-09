<?php

namespace App\Controller\Site;

use App\Entity\Tourney;
use App\Entity\TourneyGame;
use App\Entity\TourneyTeamMember;
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
use Symfony\Component\Validator\Constraints as Assert;

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

    public const FORM_NAME_SP = 'form_sp';
    public const FORM_NAME_JOIN = 'form_join';
    public const FORM_NAME_CREATE = 'form_create';
    public const FORM_NAME_UNREGISTER = 'form_unregister';
    public const FORM_NAME_CONFIRM = 'form_confirm';

    private function generateFormRegistrationCreate(): FormInterface
    {
        return $this->createNamedFormBuilder(self::FORM_NAME_CREATE)
            ->add('id', HiddenType::class, ['required' => true])
            ->add('name', TextType::class, ['label' => 'Name', 'required' => true, 'constraints' => [new Assert\NotBlank()]])
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

        return $this->createNamedFormBuilder(self::FORM_NAME_JOIN)
            ->add('id', HiddenType::class, ['required' => true])
            ->add('submit', SubmitType::class, ['label' => 'Beitreten'])
            ->add('team', ChoiceType::class)
            ->addEventListener(FormEvents::POST_SET_DATA, $fun)
            ->addEventListener(FormEvents::PRE_SUBMIT, $fun)
            ->getForm();
    }

    private function generateFormRegistrationSinglePlayer(): FormInterface
    {
        return $this->createNamedFormBuilder(self::FORM_NAME_SP)
            ->add('id', HiddenType::class, ['required' => true])
            ->add('submit', SubmitType::class, ['label' => 'Teilnehmen'])
            ->getForm();
    }

    private function generateFormUnregister(): FormInterface
    {
        return $this->createNamedFormBuilder(self::FORM_NAME_UNREGISTER)
            ->add('id', HiddenType::class, ['required' => true])
            ->add('submit', SubmitType::class, ['label' => 'Abmelden'])
            ->getForm();
    }

    private function generateFormConfirm(): FormInterface
    {
        return $this->createNamedFormBuilder(self::FORM_NAME_CONFIRM)
            ->add('id', HiddenType::class, ['required' => true])
            ->add('accept', SubmitType::class, ['label' => 'Aufnehmen'])
            ->add('decline', SubmitType::class, ['label' => 'Ablehnen'])
            ->getForm();
    }

    private function isFormSubmitted(Request $request, FormInterface $form): ?int
    {
        if (!$request->request->has($form->getName())) {
            return null;
        }
        $form->handleRequest($request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            return null;
        }

        $id = $form->get('id')->getData();
        if (!is_numeric($id)) {
            throw $this->createBadRequestHttpException();
        }
        return intval($id);
    }

    private function handleRegistrationForm(Request $request, FormInterface $form, callable $getTeam): ?Tourney
    {
        $user = ($u = $this->getUser()) ? $u->getUser() : null;
        $id = $this->isFormSubmitted($request, $form);

        if (is_null($user) || is_null($id)) {
            return null;
        }

        $tourney = $this->service->getTourneyWithTeams($id);
        if (is_null($tourney)) {
            $this->addFlash('error', 'Fehler beim Anmelden: Turnier nicht gefunden.');
            return null;
        }

        $team = $getTeam($form);
        try {
            $this->service->userRegister($tourney, $user, $team);
        } catch (ServiceException $e) {
            $this->addFlash('error',
                'Fehler beim Anmelden: ' .
                match ($e->getCode()) {
                    ServiceException::CAUSE_IN_USE => 'Turnier registrierung ist nicht (mehr) offen.',
                    ServiceException::CAUSE_EXIST => 'User ist bereits registriert.',
                    ServiceException::CAUSE_FORBIDDEN => 'User hat nicht genug Token',
                    ServiceException::CAUSE_INCONSISTENT => 'Teamname existiert schon.',
                    ServiceException::CAUSE_FULL => 'Team ist schon voll',
                    default => 'unbekannter Fehler.'
                }
            );
        }
        return $tourney;
    }

    private function handleUnregisterForm(Request $request): ?Tourney
    {
        $form = $this->generateFormUnregister();
        $user = ($u = $this->getUser()) ? $u->getUser() : null;
        $id = $this->isFormSubmitted($request, $form);
        if (is_null($user) || is_null($id)) {
            return null;
        }

        $tourney = $this->service->getTourneyWithTeams($id);
        if (is_null($tourney)) {
            $this->addFlash('error', 'Fehler beim Anmelden: Turnier nicht gefunden.');
            return null;
        }

        try {
            $this->service->userUnregister($tourney, $user);
        } catch (ServiceException $e) {
            $this->addFlash('error',
                'Fehler beim Abmelden: ' .
                match ($e->getCode()) {
                    ServiceException::CAUSE_IN_USE => 'Turnier registrierung ist nicht (mehr) offen.',
                    ServiceException::CAUSE_DONT_EXIST => 'User ist nicht registriert.',
                    default => 'unbekannter Fehler.'
                }
            );
        }
        return $tourney;
    }

    private function handleConfirmForm(Request $request): ?Tourney
    {
        $form = $this->generateFormConfirm();
        $user = ($u = $this->getUser()) ? $u->getUser() : null;
        $id = $this->isFormSubmitted($request, $form);
        if (is_null($user) || is_null($id)) {
            return null;
        }

        $accept = $form->get('accept')->isClicked();
        $ttm = $this->service->getTourneyTeamMember($id);

        if (is_null($ttm)) {
            $this->addFlash('warning', 'User war nicht mehr angemeldet.');
            return null;
        }

        $tourney = $ttm->getTeam()->getTourney();

        try {
            $this->service->userConfirmTeamMember($ttm, $user, $accept);
        } catch (ServiceException $e) {
            $this->addFlash('error',
                'Fehler: ' .
                match ($e->getCode()) {
                    ServiceException::CAUSE_IN_USE => 'Turnier registrierung ist nicht (mehr) offen.',
                    ServiceException::CAUSE_DONT_EXIST => 'User ist nicht registriert.',
                    ServiceException::CAUSE_INVALID => 'User ist bereits akzeptiert.',
                    ServiceException::CAUSE_FORBIDDEN => 'User darf nicht akzeptieren.',
                    default => 'unbekannter Fehler.'
                }
            );
        }
        return $tourney;
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

        $mayRegister = $this->service->userMayRegister($user);

        $show = null;
        $show ??= $this->handleRegistrationForm($request, $this->generateFormRegistrationSinglePlayer(), fn ($form) => null);
        $show ??= $this->handleRegistrationForm($request, $this->generateFormRegistrationCreate(), fn ($form) => $form->get('name')->getData());
        $show ??= $this->handleRegistrationForm($request, $this->generateFormRegistrationJoin(), fn ($form) => $form->get('team')->getData());
        $show ??= $this->handleUnregisterForm($request);
        $show ??= $this->handleConfirmForm($request);

        $forms = array();
        $userTeams = array();
        $token = null;

        if ($mayRegister) {
            $token = $this->service->calculateUserToken($user);
            foreach ($this->service->getRegistrableTourneys($user) as $t) {
                if ($t->isSinglePlayer()) {
                    $forms[$t->getId()] = [self::FORM_NAME_SP => $this->generateFormRegistrationSinglePlayer()->setData(['id' => $t->getId()])->createView()];
                } else {
                    $forms[$t->getId()] = [
                        self::FORM_NAME_JOIN => $this->generateFormRegistrationJoin()->setData(['id' => $t->getId()])->createView(),
                        self::FORM_NAME_CREATE => $this->generateFormRegistrationCreate()->setData(['id' => $t->getId()])->createView(),
                    ];
                }
            }
        }
        foreach ($this->service->getRegisteredTeams($user) as $ttm) {
            $team = $ttm->getTeam();
            $t = $team->getTourney();
            $userTeams[$t->getId()] = $ttm;
            if ($this->service->userCanModifyRegistration($team->getTourney())) {
                $forms[$t->getId()] = [
                    self::FORM_NAME_UNREGISTER => $this->generateFormUnregister()->setData(['id' => $t->getId()])->createView(),
                    self::FORM_NAME_CONFIRM => [],
                ];
                if ($ttm->isAccepted()) {
                    foreach ($team->getMembers() as $member) {
                        if ($member->isAccepted()){
                            continue;
                        }
                        $forms[$t->getId()][self::FORM_NAME_CONFIRM][$member->getId()] = $this->generateFormConfirm()->setData(['id' => $member->getId()])->createView();
                    }
                }
            }
        }
        $combine = fn (callable $f, array $a) => array_combine(array_map($f, $a), $a);
        $userPendingGames = $combine(fn ($g) => $g->getTourney()->getId(), $this->service->getPendingGames($user));

        return $this->render('site/tourney/index.html.twig', [
            'tourneys' => $tourneys,
            'may_register' => $mayRegister,
            'teams_registered' => $userTeams,
            'games_pending' => $userPendingGames,
            'token' => $token,
            'forms' => $forms,
            'show' => $show,
        ]);
    }

    #[Route(path: '/tourney/{id}', name: 'tourney_show')]
    public function byId(int $id): Response
    {
        $tourney = $this->service->getTourneyWithTeams($id);
        if (is_null($tourney) || !$tourney->showTree()) {
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