<?php

namespace App\Controller\Admin;

use App\Controller\HttpExceptionTrait;
use App\Entity\Tourney;
use App\Entity\TourneyGame;
use App\Entity\TourneyRules;
use App\Entity\TourneyStage;
use App\Entity\TourneyTeam;
use App\Entity\TourneyTeamMember;
use App\Entity\User;
use App\Exception\ServiceException;
use App\Form\TourneyType;
use App\Form\TourneyTeamType;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Service\TourneyService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;

#[Route(path: '/tourney', name: 'tourney')]
class TourneyController extends AbstractController
{
    private TourneyService $service;
    private IdmRepository $userRepo;
    private LoggerInterface $logger;

    private const CSRF_TOKEN_MODIFY = 'tourneyModifyToken';
    private const CSRF_TOKEN_ADVANCE = 'tourneyAdvanceToken';
    private const CSRF_TOKEN_DELETE = 'tourneyDeleteToken';

    use HttpExceptionTrait;

    public function __construct(TourneyService $service, IdmManager $manager, LoggerInterface $logger)
    {
        $this->service = $service;
        $this->userRepo = $manager->getRepository(User::class);
        $this->logger = $logger;
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
            $tourney = $form->getData();
            $this->service->create($tourney);
            $this->addFlash('success', "Turnier {$tourney->getName()} wurde angelegt.");
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

        $roots = $this->service->getRoots($tourney);
        
        // Convert roots to tree structure for display
        $calc = function(array $a) {
            $root = $a[0];
            $max_level = $a[1];
            $array = [[$root]];
            $level = 0;
            $next = true;
            while ($next) {
                $array[] = [];
                $next = false;
                foreach ($array[$level] as $game) {
                    if (is_null($game)) {
                        $array[$level + 1][] = null;
                        $array[$level + 1][] = null;
                        continue;
                    }
                    $next = true;
                    $array[$level + 1][] = is_null($game) ? null : $game->getChild(true);
                    $array[$level + 1][] = is_null($game) ? null : $game->getChild(false);
                }
                $level++;
                if ($max_level > 0 && $level > $max_level) {
                    break;
                }
            }
            array_pop($array);
            array_pop($array);
            return array_reverse($array);
        };

        $trees = array_map($calc, $roots);

        return $this->render('admin/tourney/details.html.twig', [
            'tourney' => $tourney,
            'csrf_token_advance' => self::CSRF_TOKEN_ADVANCE,
            'csrf_token_modify' => self::CSRF_TOKEN_MODIFY,
            'group_tables' => $this->service->getGroupTables($tourney),
            'trees' => $trees,
        ]);
    }

    #[Route(path: '/seed/{id}', name: '_seed', methods: ['GET', 'POST'])]
    public function seed(Request $request, Tourney $tourney): Response
    {
        if ($tourney->getStatus() != TourneyStage::Seeding) {
            throw $this->createNotFoundException('Tourney is not in seeding stage.');
        }

        $teams = $tourney->getTeams()->toArray();
        
        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, ['label' => 'Seed speichern'])
            ->setAction($request->getUri())
            ->getForm();

        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Get the team order from POST data
            $seedOrder = $request->request->all('seed');
            
            if (empty($seedOrder) || !is_array($seedOrder)) {
                $this->addFlash('error', 'Keine gültige Seed-Reihenfolge übermittelt.');
                return $this->redirectToRoute('admin_tourney_details', ['id' => $tourney->getId()]);
            }

            // Build ordered team array based on submitted IDs
            $orderedTeams = [];
            foreach ($seedOrder as $teamId) {
                foreach ($teams as $team) {
                    if ($team->getId() == $teamId) {
                        $orderedTeams[] = $team;
                        break;
                    }
                }
            }

            // Validate that all teams are included
            if (count($orderedTeams) !== count($teams)) {
                $this->addFlash('error', 'Seed-Reihenfolge ist unvollständig.');
                return $this->redirectToRoute('admin_tourney_details', ['id' => $tourney->getId()]);
            }

            try {
                $this->service->seed($tourney, $orderedTeams);
                $this->addFlash('success', 'Seed wurde erfolgreich gespeichert.');
            } catch (ServiceException $e) {
                $this->addFlash('error', 'Seed konnte nicht gespeichert werden: ' . $e->getMessage());
            }
            
            return $this->redirectToRoute('admin_tourney_details', ['id' => $tourney->getId()]);
        }

        return $this->render('admin/tourney/seed.html.twig', [
            'tourney' => $tourney,
            'teams' => $teams,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/game-result/{id}', name: '_game_result')]
    public function enterGameResult(Request $request, TourneyGame $game): Response
    {
        $tourney = $game->getTourney();
        if (!$tourney->getMode()->canHaveGames()) {
            throw $this->createNotFoundException();
        }
        if (!$game->isPending() && !$game->isDone()) {
            throw $this->createBadRequestHttpException();
        }
        $form = $this->createFormBuilder()
            ->add('scoreA', NumberType::class, ['required' => true, 'attr' => ['pattern' => '[0-9]{1,2}', 'size' =>'2'], 'constraints' => [new Assert\PositiveOrZero()]])
            ->add('scoreB', NumberType::class, ['required' => true, 'attr' => ['pattern' => '[0-9]{1,2}', 'size' =>'2'], 'constraints' => [new Assert\PositiveOrZero()]])
            ->add('submit', SubmitType::class, ['label' => 'Speichern'])
            ->setAction($request->getUri())
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $scoreA = $form->get('scoreA')->getData();
                $scoreB = $form->get('scoreB')->getData();
                $this->service->logResult($game, $scoreA, $scoreB);
                $this->addFlash('success', 'Ergebnis wurde erfolgreich gesetzt.');
            } catch (ServiceException $e) {
                $this->addFlash('error', 'Ergebnis konnte nicht gesetzt werden: ' . $e->getMessage());
            }
            return $this->redirectToRoute('admin_tourney_details', ['id' => $tourney->getId()]);
        }

        return $this->render('admin/tourney/gameresult.html.twig', [
            'game' => $game,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/result/{id}', name: '_result')]
    public function enterResult(Request $request, Tourney $tourney): Response
    {
        if ($tourney->getMode()->canHaveGames()) {
            throw $this->createNotFoundException();
        }

        $choices = $tourney->getTeams()->toArray();
        $label = $tourney->isSinglePlayer()
            ? fn (TourneyTeam $t) => $this->userRepo->findOneById($t->getMembers()[0]->getGamer())->getNickname()
            : fn (TourneyTeam $t) => $t->getName();
        $form = $this->createFormBuilder()
            ->add('first', ChoiceType::class, [
                'label' => 'Platz 1',
                'choices' => $choices,
                'choice_value' => 'id',
                'choice_label' => $label,
                'required' => true,
                'multiple' => false,
                'expanded' => false
            ])
            ->add('second', ChoiceType::class, [
                'label' => 'Platz 2',
                'choices' => $choices,
                'choice_value' => 'id',
                'choice_label' => $label,
                'required' => true,
                'multiple' => false,
                'expanded' => false
            ])
            ->add('third', ChoiceType::class, [
                'label' => 'Platz 3',
                'choices' => $choices,
                'choice_value' => 'id',
                'choice_label' => $label,
                'required' => false,
                'multiple' => false,
                'expanded' => false
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Speichern',
            ])
            ->setAction($request->getUri())
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            try {
                $this->service->setResult($tourney, $data['first'], $data['second'], $data['third']);
                $this->addFlash('success', 'Podium erfolgreich gesetzt.');
            } catch (ServiceException $e) {
                $this->addFlash('error', 'Podium konnte nicht gesetzt werden: ' . $e->getMessage());
            }
            return $this->redirectToRoute('admin_tourney');
        }

        return $this->render('admin/tourney/result.html.twig', [
            'tourney' => $tourney,
            'form' => $form->createView(),
        ]);
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
        $redirect = $this->resolveRedirectPath($request);
        if ($redirect) {
            return $this->redirect($redirect);
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
        $redirect = $this->resolveRedirectPath($request);
        if ($redirect) {
            return $this->redirect($redirect);
        }
        return $this->redirectToRoute('admin_tourney');
    }

    #[Route(path: '/remove-team/{id}', name: '_remove_team', methods: ['POST'])]
    public function unregister(Request $request, TourneyTeam $team): Response
    {
        $tourney = $team->getTourney();
        if ($tourney->getStatus() != TourneyStage::Registration) {
            throw $this->createNotFoundException();
        }
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_MODIFY, $token)) {
            throw $this->createAccessDeniedException('The CSRF token is invalid.');
        }
        try {
            $this->service->teamUnregister($team);
            $this->addFlash('success', "Team {$team->getName()} wurde von Tourney {$tourney->getName()} abgemeldet.");
        } catch (ServiceException $e) {
            $this->addFlash('error', $e->getMessage());
        }
        $redirect = $this->resolveRedirectPath($request);
        if ($redirect) {
            return $this->redirect($redirect);
        }
        return $this->redirectToRoute('admin_tourney');
    }

    #[Route(path: '/add-team/{id}', name: '_add_team', methods: ['GET', 'POST'])]
    public function addTeam(Request $request, Tourney $tourney): Response
    {
        if (!$tourney->getStatus()->canRegister()) {
            throw $this->createNotFoundException();
        }

        $team = new TourneyTeam();
        $team->setTourney($tourney);
        if (!$tourney->isSinglePlayer() && $team->getMembers()->count() === 0) {
            $team->addMember(new TourneyTeamMember());
        }

        $form = $this->createForm(TourneyTeamType::class, $team, ['tourney' => $tourney]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->service->createTeam($team);
                $this->addFlash('success', "Team {$team->getName()} wurde erstellt.");
            } catch (ServiceException $e) {
                $this->addFlash('error', 'Team konnte nicht erstellt werden: ' . $e->getMessage());
            }
            
            $redirect = $this->resolveRedirectPath($request);
            return $redirect ? $this->redirect($redirect) : $this->redirectToRoute('admin_tourney_details', ['id' => $tourney->getId()]);
        }

        // Bei Validierungsfehlern
        if ($form->isSubmitted()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            if ($errors) {
                $this->addFlash('error', 'Validierungsfehler: ' . implode(', ', $errors));
            }
            
            $redirect = $this->resolveRedirectPath($request);
            return $redirect ? $this->redirect($redirect) : $this->redirectToRoute('admin_tourney_details', ['id' => $tourney->getId()]);
        }

        $redirectTarget = $this->resolveRedirectPath($request);

        return $this->render('admin/tourney/team_form.html.twig', [
            'form' => $form->createView(),
            'tourney' => $tourney,
            'team' => $team,
            'redirect' => $redirectTarget,
            'is_edit' => false,
        ]);
    }

    #[Route(path: '/edit-team/{id}', name: '_edit_team', methods: ['GET', 'POST'])]
    public function editTeam(Request $request, TourneyTeam $team): Response
    {
        $tourney = $team->getTourney();
        if (!$tourney->getStatus()->canRegister()) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(TourneyTeamType::class, $team, ['tourney' => $tourney]);

        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->service->updateTeam($team);
                $this->addFlash('success', "Team {$team->getName()} wurde aktualisiert.");
            } catch (ServiceException $e) {
                $this->addFlash('error', 'Team konnte nicht aktualisiert werden: ' . $e->getMessage());
            }
            
            $redirect = $this->resolveRedirectPath($request);
            return $redirect ? $this->redirect($redirect) : $this->redirectToRoute('admin_tourney_details', ['id' => $tourney->getId()]);
        }

        // Bei Validierungsfehlern
        if ($form->isSubmitted()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            if ($errors) {
                $this->addFlash('error', 'Validierungsfehler: ' . implode(', ', $errors));
            }
            
            $redirect = $this->resolveRedirectPath($request);
            return $redirect ? $this->redirect($redirect) : $this->redirectToRoute('admin_tourney_details', ['id' => $tourney->getId()]);
        }

        $redirectTarget = $this->resolveRedirectPath($request);

        return $this->render('admin/tourney/team_form.html.twig', [
            'form' => $form->createView(),
            'tourney' => $tourney,
            'team' => $team,
            'redirect' => $redirectTarget,
            'is_edit' => true,
        ]);
    }

    private function resolveRedirectPath(Request $request): ?string
    {
        $redirect = $request->get('redirect');
        if (is_string($redirect) && str_starts_with($redirect, '/')) {
            return $redirect;
        }

        return null;
    }
}
