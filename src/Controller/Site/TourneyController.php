<?php

namespace App\Controller\Site;

use App\Controller\HttpExceptionTrait;
use App\Entity\Tourney;
use App\Entity\TourneyGame;
use App\Entity\TourneyRules;
use App\Exception\ServiceException;
use App\Service\PermissionService;
use App\Service\TourneyService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    use HttpExceptionTrait;

    private function createNamedFormBuilder(string $name): FormBuilderInterface
    {
        return $this->container->get('form.factory')->createNamedBuilder($name);
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
    public const FORM_NAME_RESULT = 'form_result';

    private function generateFormRegistrationCreate(): FormInterface
    {
        return $this->createNamedFormBuilder(self::FORM_NAME_CREATE)
            ->add('id', HiddenType::class, ['required' => true])
            ->add('name', TextType::class, ['label' => 'Name', 'required' => true, 'attr' => ['maxlength' => TourneyService::TEAM_NAME_MAX_LENGTH], 'constraints' => [new Assert\NotBlank()]])
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
            $teams = $tourney->getTeams();
            if ($teams->isEmpty()) {
                $event->getForm()
                    ->remove('submit')
                    ->add('submit', SubmitType::class, ['label' => 'Beitreten', 'disabled' => true]);
            }
            $event->getForm()
                ->remove('team')
                ->add('team', ChoiceType::class, [
                    'label' => 'Team', 'required' => true, 'multiple' => false,
                    'choices' => $teams,
                    'disabled' => $teams->isEmpty(),
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

    private function generateFormResult(): FormInterface
    {
        return $this->createNamedFormBuilder(self::FORM_NAME_RESULT)
            ->add('id', HiddenType::class, ['required' => true])
            ->add('scoreA', NumberType::class, ['required' => true, 'attr' => ['pattern' => '[0-9]{1-2}', 'size' =>'2'], 'constraints' => [new Assert\PositiveOrZero()]])
            ->add('scoreB', NumberType::class, ['required' => true, 'attr' => ['pattern' => '[0-9]{1-2}', 'size' =>'2'], 'constraints' => [new Assert\PositiveOrZero()]])
            ->add('submit', SubmitType::class, ['label' => 'Speichern'])
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
                    ServiceException::CAUSE_FORBIDDEN => 'User darf sich nicht registrieren.',
                    ServiceException::CAUSE_INCONSISTENT => 'Teamname existiert schon.',
                    ServiceException::CAUSE_FULL => 'Team ist schon voll',
                    ServiceException::CAUSE_TOO_LONG => "Teamname darf nicht länger als " . TourneyService::TEAM_NAME_MAX_LENGTH . ' Zeichen lang sein.',
                    ServiceException::CAUSE_INVALID => "Ausgewähltem Team kann nicht beigetreten werden.",
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

    private function handleResultForm(Request $request): ?Tourney
    {
        $form = $this->generateFormResult();
        $user = ($u = $this->getUser()) ? $u->getUser() : null;
        $id = $this->isFormSubmitted($request, $form);
        if (is_null($user) || is_null($id)) {
            return null;
        }

        $game = $this->service->getGame($id);
        if (is_null($game)){
            return null;
        }

        $tourney = $game->getTourney();
        $scoreA = $form->get('scoreA')->getData();
        $scoreB = $form->get('scoreB')->getData();

        try {
            $this->service->logResultUser($game, $user, $scoreA, $scoreB);
        } catch (ServiceException $e) {
            $this->addFlash('error',
                'Fehler: ' .
                match ($e->getCode()) {
                    ServiceException::CAUSE_INCONSISTENT => 'Nur Spieler dieses Spiels dürfen das Ergebnis eintragen.',
                    ServiceException::CAUSE_FORBIDDEN => 'Der Verlierer muss das Ergebnis eintragen.',
                    ServiceException::CAUSE_INVALID => 'Gleichstand ist nicht erlaubt',
                    ServiceException::CAUSE_IN_USE => 'Turnier läuft nicht.',
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
        $podiums = array();

        foreach ($tourneys as $tourney) {
            $p = TourneyService::getPodium($tourney);
            if (!empty($p))
                $podiums[$tourney->getId()] = $p;
        }

        if (is_null($user) || !$this->service->userMayParticipate($user)) {
            return $this->render('site/tourney/index.html.twig', [
                'tourneys' => $tourneys,
                'podiums' => $podiums,
                'participates' => false,
            ]);
        }

        $show = null;
        $show ??= $this->handleRegistrationForm($request, $this->generateFormRegistrationSinglePlayer(), fn ($form) => null);
        $show ??= $this->handleRegistrationForm($request, $this->generateFormRegistrationCreate(), fn ($form) => $form->get('name')->getData());
        $show ??= $this->handleRegistrationForm($request, $this->generateFormRegistrationJoin(), fn ($form) => $form->get('team')->getData());
        $show ??= $this->handleUnregisterForm($request);
        $show ??= $this->handleConfirmForm($request);
        $show ??= $this->handleResultForm($request);

        if ($show) {
            return $this->redirectToRoute('tourney', ['_fragment' => 'tourney-'.$show->getId()]);
        }

        $forms = array();
        $userTeams = array();
        $userActiveGames = array();
        $token = null;

        $mayRegister = $this->service->registrationOpen();
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
            if ($this->service->userCanModifyRegistration($team->getTourney(), $user)) {
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
        foreach ($this->service->getActiveGames($user) as $game) {
            $t = $game->getTourney();
            $userActiveGames[$t->getId()] = $game;
            if (!$game->isPending()) {
                continue;
            }
            $forms[$t->getId()] = [self::FORM_NAME_RESULT => $this->generateFormResult()->setData(['id' => $game->getId()])->createView()];
        }

        return $this->render('site/tourney/index.html.twig', [
            'tourneys' => $tourneys,
            'participates' => true,
            'teams_registered' => $userTeams,
            'games_active' => $userActiveGames,
            'podiums' => $podiums,
            'token' => $token,
            'forms' => $forms,
            'show' => $show,
        ]);
    }

    #[Route(path: '/tourney/{id}', name: 'tourney_show')]
    public function byId(int $id): Response
    {
        $tourney = $this->service->getTourneyWithTeams($id);

        if (is_null($tourney) || !$tourney->getMode()->hasTree()) {
            throw $this->createNotFoundException();
        }
        $admin = $this->isGranted('ROLE_' . PermissionService::ADMIN_TOURNEY);
        if (!$tourney->getStatus()->hasTree($admin)) {
            throw $this->createNotFoundException();
        }

        $final = TourneyService::getFinal($tourney);
        if (is_null($final)) {
            throw $this->createNotFoundException();
        }

        $gamers = $this->service->getAllUsersOfTourney($tourney);
        $this->userService->preloadUsers($gamers);

        $user = ($u = $this->getUser()) ? $u->getUser() : null;
        $ownTeam = null;
        if (!is_null($user) && !is_null($ttm = $this->service->getTeamMemberByTourneyAndUser($tourney, $user))) {
            $ownTeam = $ttm->getTeam();
        }

        $calc = function(TourneyGame $root) {
            $array = [[$root]];
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
            return array_reverse($array);
        };

        if ($tourney->getMode() == TourneyRules::DoubleElimination) {
            $array = [0 => [$final]];
            $array_winner = $calc($final->getChild(true));
            $array_loser = $calc($final->getChild(false));
        } else {
            $array = $calc($final);
            $array_loser = null;
            $array_winner = null;
        }

        return $this->render('site/tourney/show.html.twig', [
            'tourney' => $tourney,
            'tree_winner' => $array_winner,
            'tree_loser' => $array_loser,
            'tree' => $array,
            'team' => $ownTeam,
        ]);
    }
}