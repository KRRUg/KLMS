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
    public function index()
    {
        $clans = $this->clanRepo->findAll();

        return $this->render('site/clan/list.html.twig', [
            'clans' => $clans,
        ]);
    }

//    /**
//     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
//     * @Route("/clan/join", name="clan_join", methods={"GET", "POST"})
//     */
//    public function join(Request $request)
//    {
//        $data = [];
//
//        $uuid = $request->query->get('uuid');
//        if ($uuid) {
//            $clan = $this->clanRepo->findOneById($uuid);
//            if ($clan) {
//                $data = [$clan->getName() => $uuid];
//            }
//        }
//
//        $form = $this->createForm(ClanJoinType::class, $data, [
//            'data-remote-target' => $this->generateUrl('api_clans'),
//        ]);
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            $clanform = $form->getData();
//
//            // TODO check this!
//            $clan = $this->clanRepo->findOneById($clanform['name']);
//
//            // check join password
//            if (!$this->clanRepo->authenticate($clan->getName(), $clanform['joinPassword'])) {
//                $form->get('joinPassword')->addError(new FormError('Das angegebene JoinPasswort ist falsch!'));
//
//                return $this->render('site/clan/join.html.twig', [
//                    'form' => $form->createView(),
//                ]);
//            }
//            try {
//                $user = $this->getUser()->getUser();
//                $clan->getUsers()[] = $user;
//                $this->im->flush();
//                $this->addFlash('info', 'Clan erfolgreich beigetreten!');
//            } catch (UserServiceException $e) {
//                $this->addFlash('error', 'Unbekannter Fehler beim Beitreten!');
//            }
//
//            return $this->redirectToRoute('clan_show', ['uuid' => $clan->getUuid()]);
//        }
//
//        return $this->render('site/clan/join.html.twig', [
//            'form' => $form->createView(),
//        ]);
//    }

    /**
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     * @Route("/clan/create", name="clan_create", methods={"GET", "POST"})
     *
     * @return RedirectResponse|Response
     */
    public function create(Request $request)
    {
        $clan = new Clan();
        $form = $this->createForm(ClanType::class, $clan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $clan = $form->getData();
            try {
                $this->im->persist($clan);
                $this->im->flush();
                $clan->getAdmins()[] = $this->getUser()->getUser();
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
