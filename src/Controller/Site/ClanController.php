<?php

namespace App\Controller\Site;

use App\Entity\Clan;
use App\Entity\User;
use App\Form\ClanType;
use App\Idm\Exception\PersistException;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Service\GamerService;
use App\Service\SettingService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
 */
class ClanController extends AbstractController
{
    private const CSRF_TOKEN_DELETE = "clanDeleteToken";
    private const CSRF_TOKEN_MEMBER_EDIT = "clanMemberEditToken";
    private const CSRF_TOKEN_MEMBER_LEAVE = "clanMemberLeaveToken";

    private IdmManager $im;
    private IdmRepository $clanRepo;
    private IdmRepository $userRepo;
    private SettingService $settingService;
    private GamerService $gamerService;

    public function __construct(IdmManager $manager, SettingService $settingService, GamerService $gamerService)
    {
        $this->im = $manager;
        $this->clanRepo = $manager->getRepository(Clan::class);
        $this->userRepo = $manager->getRepository(User::class);
        $this->settingService = $settingService;
        $this->gamerService = $gamerService;
    }

    private const SHOW_LIMIT = 10;

    /**
     * @Route("/clan", name="clan", methods={"GET"})
     */
    public function index(Request $request)
    {
        if (!$this->settingService->getOrDefault('community.enabled', false)) {
            throw $this->createNotFoundException();
        }

        $search = $request->query->get('q', '');
        $page = $request->query->getInt('page', 1);
        $page = $page < 1 ? 1 : $page;

        if ($this->settingService->getOrDefault('community.all', false)) {
            $collection = $this->clanRepo->findFuzzy($search);
            $clans = $collection->getPage($page, self::SHOW_LIMIT);
            $count = $collection->count();
        } else {
            $clans = array_values($this->gamerService->getClans());
            if (!empty($search)) {
                $clans = array_filter($clans, function (Clan $u) use ($search) {
                    return stripos($u->getClantag(), $search) !== false || stripos($u->getName(), $search) !== false;
                });
            }
            usort($clans, function (Clan $a, Clan $b) { return $a->getName() <=> $b->getName(); });
            $clans = array_slice($clans, ($page - 1) * self::SHOW_LIMIT, self::SHOW_LIMIT);
            $count = count($clans);
        }

        return $this->render('site/clan/list.html.twig', [
            'search' => $search,
            'clans' => $clans,
            'page' => $page,
            'total' => $count,
            'limit' => self::SHOW_LIMIT,
        ]);
    }

    private function createJoinForm(Clan $clan): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('clan_join', ['uuid' => $clan->getUuid()]))
            ->add('password', PasswordType::class, ['label' => "Passwort", 'attr' => ['autofocus' => 'autofocus']])
            ->getForm();
    }

    /**
     * @Route("/clan/{uuid}/leave", name="clan_leave", methods={"POST"})
     */
    public function leave(string $uuid, Request $request)
    {
        $clan = $this->clanRepo->findOneById($uuid);
        if (empty($clan)) {
            throw $this->createNotFoundException('Clan not found');
        }

        $token = $request->request->get('_token');
        if(!$this->isCsrfTokenValid(self::CSRF_TOKEN_MEMBER_LEAVE, $token)) {
            throw $this->createAccessDeniedException('The CSRF token is invalid.');
        }

        $user = $this->getUser()->getUser();

        $this->removeUserFromClan($clan, $user);
        return $this->redirectToRoute('clan_show', ['uuid' => $clan->getUuid()]);
    }

    /**
     * @Route("/clan/{uuid}/member", name="clan_member_edit", methods={"POST"})
     */
    public function memberEdit(string $uuid, Request $request): RedirectResponse
    {
        $clan = $this->clanRepo->findOneById($uuid);
        if (empty($clan)) {
            throw $this->createNotFoundException('Clan not found');
        }

        $token = $request->request->get('_token');
        if(!$this->isCsrfTokenValid(self::CSRF_TOKEN_MEMBER_EDIT, $token)) {
            throw $this->createAccessDeniedException('The CSRF token is invalid.');
        }

        $admin = $this->getUser()->getUser();
        if (!$clan->isAdmin($admin)) {
            $this->addFlash('error', 'Nur Admins dürfen das.');
            return $this->redirectToRoute('clan_show', ['uuid' => $clan->getUuid()]);
        }
        $user = $this->userRepo->findOneById($request->request->get('user_uuid'));
        if (empty($user)) {
            $this->addFlash('error', 'User nicht gefunden.');
            return $this->redirectToRoute('clan_show', ['uuid' => $clan->getUuid()]);
        }
        $action = $request->request->get('action');
        switch ($action) {
            case 'kick':
                $this->removeUserFromClan($clan, $user);
                break;
            case 'promote':
                $this->setUserAdmin($clan, $user, true);
                break;
            case 'demote':
                $this->setUserAdmin($clan, $user, false);
                break;
            default:
                $this->addFlash("error", "Aktion nicht möglich.");
                break;
        }
        return $this->redirectToRoute('clan_show', ['uuid' => $clan->getUuid()]);
    }

    private function removeUserFromClan(Clan $clan, User $user)
    {
        if ($clan->isAdmin($user) && count($clan->getAdmins()) === 1) {
            $this->addFlash('warning', 'Der letzte Admin kann den Clan nicht verlassen.
             Anderen Admin festlegen oder Clan löschen!');
            return;
        }
        try {
            $clan->removeUser($user);
            $this->im->flush();
            $this->addFlash('success', 'Clan erfolgreich verlassen!');
        } catch (PersistException $e) {
            $this->addFlash('error', 'Unbekannter Fehler beim Verlassen!');
        }
    }

    private function setUserAdmin(Clan $clan, User $user, bool $admin)
    {
        if (!$admin && $clan->isAdmin($user) && count($clan->getAdmins()) === 1) {
            $this->addFlash('warning', 'Der letzte Admin muss Admin bleiben.
             Anderen Admin festlegen oder Clan löschen!');
            return;
        }
        try {
            if ($admin)
                $clan->addAdmin($user);
            else
                $clan->removeAdmin($user);
            $this->im->flush();
            $this->addFlash('success', 'Userstatus erfolgreich geändert!');
        } catch (PersistException $e) {
            $this->addFlash('error', 'Unbekannter Fehler Ändern des Userstatus!');
        }
    }

    /**
     * @Route("/clan/{uuid}/join", name="clan_join", methods={"POST"})
     */
    public function join(string $uuid, Request $request): RedirectResponse
    {
        $clan = $this->clanRepo->findOneById($uuid);
        if (empty($clan)) {
            throw $this->createNotFoundException('Clan not found');
        }

        $user = $this->getUser()->getUser();

        foreach ($clan->getUsers() as $u) {
            if ($user === $u) {
                $this->addFlash('info', "User {$user->getNickname()} ist schon in Clan {$clan->getName()}");
                return $this->redirectToRoute('clan_show', ['uuid' => $clan->getUuid()]);
            }
        }

        $form = $this->createJoinForm($clan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if ($this->clanRepo->authenticate($clan->getName(), $form['password']->getData())) {
                    if (count($clan->getUsers()))
                        $clan->getUsers()[] = $user;
                    else
                        $clan->getAdmins()[] = $user;
                    $this->im->flush();
                    $this->addFlash('success', 'Clan erfolgreich beigetreten!');
                } else {
                    $this->addFlash('error', "Falsches Passwort eingegeben!");
                }
            } catch (PersistException $e) {
                $this->addFlash('error', 'Unbekannter Fehler beim Beitreten!');
            }
        }
        return $this->redirectToRoute('clan_show', ['uuid' => $clan->getUuid()]);
    }

    /**
     * @Route("/clan/create", name="clan_create", methods={"GET", "POST"})
     */
    public function create(Request $request)
    {
        $clan = new Clan();
        $form = $this->createForm(ClanType::class, $clan, ['require_password' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $clan = $form->getData();
            try {
                $clan->setAdmins([$this->getUser()->getUser()]);
                $this->im->persist($clan);
                $this->im->flush();
                $this->addFlash('success', 'Clan erfolgreich angelegt!');
                return $this->redirectToRoute('clan_show', ['uuid' => $clan->getUuid()]);
            } catch (PersistException $e) {
                switch ($e->getCode()) {
                    case PersistException::REASON_NON_UNIQUE:
                        $this->addFlash('error', 'Clanname und/oder Tag ist schon in Verwendung');
                        break;
                    default:
                        $this->addFlash('error', 'Es ist ein unerwarteter Fehler aufgetreten');
                        break;
                }
            }
        }

        return $this->render('site/clan/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/clan/{uuid}", name="clan_show", methods={"GET"})
     *
     * @return Response
     */
    public function show(string $uuid)
    {
        $clan = $this->clanRepo->findOneById($uuid);

        $this->throwOnClanNotFound($clan);

        return $this->render('site/clan/show.html.twig', [
            'clan' => $clan,
            'form_join' => $this->createJoinForm($clan)->createView(),
            'csrf_token_member_leave' => self::CSRF_TOKEN_MEMBER_LEAVE,
            'csrf_token_member_edit' => self::CSRF_TOKEN_MEMBER_EDIT,
        ]);
    }

    private function throwOnUserNotAdminOfClan(Clan $clan)
    {
        if (!$clan->isAdmin($this->getUser()->getUser())) {
            throw $this->createAccessDeniedException('Nur Admins können den Clan bearbeiten!');
        }
    }

    private function throwOnClanNotFound(?Clan $clan)
    {
        if (empty($clan)) {
            throw $this->createNotFoundException('Clan wurde nicht gefunden.');
        }
    }

    /**
     * @Route("/clan/{uuid}/edit", name="clan_edit", methods={"GET", "POST"})
     *
     * @return AccessDeniedException|RedirectResponse|Response
     */
    public function edit(string $uuid, Request $request)
    {
        $clan = $this->clanRepo->findOneById($uuid);

        $this->throwOnClanNotFound($clan);
        $this->throwOnUserNotAdminOfClan($clan);

        $form = $this->createForm(ClanType::class, $clan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $clan = $form->getData();
            try {
                $this->im->persist($clan);
                $this->im->flush();
                $this->addFlash('info', 'Clan erfolgreich bearbeitet!');
                return $this->redirectToRoute('clan_show', ['uuid' => $clan->getUuid()]);
            } catch (PersistException $e) {
                switch ($e->getCode()) {
                    case PersistException::REASON_NON_UNIQUE:
                        $this->addFlash('error', 'Clanname und/oder Tag ist schon in Verwendung');
                        break;
                    default:
                        $this->addFlash('error', 'Es ist ein unerwarteter Fehler aufgetreten');
                        break;
                }
            }
        }

        return $this->render('site/clan/edit.html.twig', [
            'form' => $form->createView(),
            'clan' => $clan,
            'csrf_token_delete' => self::CSRF_TOKEN_DELETE
        ]);
    }

    /**
     * @Route("/clan/{uuid}/delete", name="clan_delete", methods={"POST"})
     *
     * @return AccessDeniedException|RedirectResponse|NotFoundHttpException
     */
    public function delete(string $uuid, Request $request)
    {
        $clan = $this->clanRepo->findOneById($uuid);

        $this->throwOnClanNotFound($clan);
        $this->throwOnUserNotAdminOfClan($clan);

        $token = $request->request->get('_token');
        if(!$this->isCsrfTokenValid(self::CSRF_TOKEN_DELETE, $token)) {
            throw $this->createAccessDeniedException('The CSRF token is invalid.');
        }

        try {
            $this->im->remove($clan);
            $this->im->flush();
            $this->addFlash('info', 'Clan erfolgreich gelöscht!');
        } catch (PersistException $e) {
            $this->addFlash('error', 'Es ist ein unerwarteter Fehler aufgetreten');
        }

        return $this->redirectToRoute('clan');
    }
}
