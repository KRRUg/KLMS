<?php

namespace App\Controller\Site;

use App\Entity\Clan;
use App\Entity\User;
use App\Form\ClanType;
use App\Idm\Exception\PersistException;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
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

class ClanController extends AbstractController
{
    //TODO: Better Exception/Error Handling see https://github.com/KRRUg/KLMS/blob/feature/admin-mgmt/src/Controller/BaseController.php and Admin/PermissionController.php

    private const CSRF_TOKEN_MEMBER_REMOVE = "clanMemberDeleteToken";
    private const CSRF_TOKEN_MEMBER_LEAVE = "clanMemberLeaveToken";

    private IdmManager $im;
    private IdmRepository $clanRepo;
    private IdmRepository $userRepo;

    public function __construct(IdmManager $manager)
    {
        $this->im = $manager;
        $this->clanRepo = $manager->getRepository(Clan::class);
        $this->userRepo = $manager->getRepository(User::class);
    }

    /**
     * @Route("/clan", name="clan", methods={"GET"})
     */
    public function index(Request $request)
    {
        $search = $request->query->get('q', '');
        $limit = $request->query->getInt('limit', 10);
        $page = $request->query->getInt('page', 1);

        $collection = $this->clanRepo->findFuzzy($search);
        $clans = $collection->getPage($page, $limit);

        if (empty($clans)) {
            throw $this->createNotFoundException();
        }

        return $this->render('site/clan/list.html.twig', [
            'search' => $search,
            'clans' => $clans,
            'total' => sizeof($clans),
            'limit' => $limit,
            'page' => $page,
        ]);
    }

    private function createJoinForm(Clan $clan): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('clan_join', ['uuid' => $clan->getUuid()]))
            ->add('password', PasswordType::class, ['label' => "Passwort"])
            ->getForm();
    }

    /**
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
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
            $this->addFlash('error', 'The CSRF token is invalid.');
            return $this->redirectToRoute('clan_show', ['uuid' => $clan->getUuid()]);
        }

        $user = $this->getUser()->getUser();

        $found = false;
        foreach ($clan->getUsers() as $key => $u) {
            if ($user === $u) {
                $found = $key;
                break;
            }
        }
        if ($found === false) {
            $this->addFlash('info', "User {$user->getNickname()} ist nicht in Clan {$clan->getName()}");
        } else {
            try {
                unset($clan->getUsers()[$found]);
                $this->im->flush();
                $this->addFlash('success', 'Clan erfolgreich verlassen!');
            } catch (PersistException $e) {
                $this->addFlash('error', 'Unbekannter Fehler beim Verlassen!');
            }
        }
        return $this->redirectToRoute('clan_show', ['uuid' => $clan->getUuid()]);
    }

    /**
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     * @Route("/clan/{uuid}/join", name="clan_join", methods={"POST"})
     */
    public function join(string $uuid, Request $request)
    {
        $clan = $this->clanRepo->findOneById($uuid);
        if (empty($clan)) {
            throw $this->createNotFoundException('Clan not found');
        }

        $user = $this->getUser()->getUser();

        $found = false;
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
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     * @Route("/clan/create", name="clan_create", methods={"GET", "POST"})
     *
     * @return RedirectResponse|Response
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
        ]);
    }

    private function throwOnUserNotAdminOfClan(Clan $clan)
    {
        if (!in_array($this->getUser()->getUser(), $clan->getAdmins()->toArray())) {
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
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
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
            'csrf_token_member_remove' => self::CSRF_TOKEN_MEMBER_REMOVE,
        ]);
    }

    /**
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     * @Route("/clan/{uuid}", name="clan_delete", methods={"DELETE"})
     *
     * @return AccessDeniedException|RedirectResponse|NotFoundHttpException
     */
    public function delete(string $uuid)
    {
        $clan = $this->clanRepo->findOneById($uuid);

        $this->throwOnClanNotFound($clan);
        $this->throwOnUserNotAdminOfClan($clan);

        try {
            $this->im->remove($clan);
            $this->im->flush();
            $this->addFlash('info', 'Clan erfolgreich gelöscht!');
        } catch (PersistException $e) {
            $this->addFlash('error', 'Es ist ein unerwarteter Fehler aufgetreten');
        }

        return $this->redirectToRoute('user_profile');
    }

    /**
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     * @Route("/clan/{uuid}/member/remove", name="clan_member_remove", methods={"POST"})
     *
     * @return AccessDeniedException|NotFoundHttpException|RedirectResponse
     */
    public function removeMember(string $uuid, Request $request)
    {
        $token = $request->request->get('_token');
        if(!$this->isCsrfTokenValid(self::CSRF_TOKEN_MEMBER_REMOVE, $token)) {
            $this->addFlash('error', 'The CSRF token is invalid.');
            return $this->redirectToRoute('clan');
        }

        $clan = $this->clanRepo->findOneById($uuid);

        $this->throwOnClanNotFound($clan);
        $this->throwOnUserNotAdminOfClan($clan);

        if (empty($user_uuid = $request->request->get('user_uuid'))) {
            $this->createNotFoundException('No User supplied in POST (user_uuid)');
        }

        $user = $this->userRepo->findOneById($user_uuid);

        if (!$user) {
            $this->createNotFoundException('User supplied in POST not found or invalid');
        }

        $nickname = $user->getNickname();
        try {
            $clan->removeUser($user);
            $this->im->flush();
            $this->addFlash('info', "User {$nickname} erfolgreich aus dem Clan entfernt!");
        } catch (PersistException $e) {
            $this->addFlash('error', 'Es ist ein unerwarteter Fehler aufgetreten');
        }

        return $this->redirectToRoute('clan_edit', ['uuid' => $clan->getUuid()]);
    }
}
