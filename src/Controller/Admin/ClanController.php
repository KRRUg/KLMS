<?php

namespace App\Controller\Admin;

use App\Entity\Clan;
use App\Entity\User;
use App\Form\ClanType;
use App\Idm\Exception\PersistException;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_ADMIN_USER")
 */
class ClanController extends AbstractController
{
    // TODO: Better Exception/Error Handling see https://github.com/KRRUg/KLMS/blob/feature/admin-mgmt/src/Controller/BaseController.php and Admin/PermissionController.php
    private const CSRF_TOKEN_DELETE = 'clanDeleteToken';
    private const CSRF_TOKEN_MEMBER_EDIT = 'clanMemberAddToken';

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
    public function index(): \Symfony\Component\HttpFoundation\Response
    {
        $clans = $this->clanRepo->findAll();

        return $this->render('admin/clan/index.html.twig', [
            'clans' => $clans,
            'csrf_token_delete' => self::CSRF_TOKEN_DELETE,
        ]);
    }

    /**
     * @Route("/clan/create", name="clan_create", methods={"GET", "POST"})
     */
    public function create(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $form = $this->createForm(ClanType::class, null, ['require_password' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $clan = $form->getData();
            try {
                $this->im->persist($clan);
                $this->im->flush();

                $this->addFlash('success', 'Clan erfolgreich angelegt!');

                return $this->redirectToRoute('admin_clan');
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

        return $this->render('admin/clan/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/clan/{uuid}/edit", name="clan_edit", methods={"GET", "POST"})
     */
    public function edit(string $uuid, Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $clan = $this->clanRepo->findOneById($uuid);
        if (is_null($clan)) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(ClanType::class, $clan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $clan = $form->getData();

            $this->im->persist($clan);
            $this->im->flush();

            $this->addFlash('success', 'Clan erfolgreich bearbeitet!');

            return $this->redirectToRoute('admin_clan');
        }

        return $this->render('admin/clan/edit.html.twig', [
            'clan' => $clan,
            'form' => $form->createView(),
            'csrf_token_delete' => self::CSRF_TOKEN_DELETE,
        ]);
    }

    /**
     * @Route("/clan/{uuid}", name="clan_show", methods={"GET"})
     */
    public function show(string $uuid): \Symfony\Component\HttpFoundation\Response
    {
        $clan = $this->clanRepo->findOneById($uuid);
        if (is_null($clan)) {
            throw $this->createNotFoundException();
        }

        return $this->render('admin/clan/show.html.twig', [
            'clan' => $clan,
        ]);
    }

    /**
     * @Route("/clan/{uuid}/member", name="clan_member", methods={"GET"})
     */
    public function member(string $uuid): \Symfony\Component\HttpFoundation\Response
    {
        $clan = $this->clanRepo->findOneById($uuid);
        if (is_null($clan)) {
            throw $this->createNotFoundException();
        }

        return $this->render('admin/clan/member.html.twig', [
            'clan' => $clan,
            'csrf_token_member_edit' => self::CSRF_TOKEN_MEMBER_EDIT,
        ]);
    }

    /**
     * @Route("/clan/{uuid}/member/edit", name="clan_member_edit", methods={"POST"})
     */
    public function editMember(string $uuid, Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_MEMBER_EDIT, $token)) {
            throw $this->createAccessDeniedException('The CSRF token is invalid.');
        }

        $clan = $this->clanRepo->findOneById($uuid);
        $user = $this->userRepo->findOneById($request->request->get('user_uuid'));

        if (empty($clan)) {
            throw $this->createNotFoundException();
        }
        if (empty($user)) {
            $this->addFlash('error', 'Ungültiger User ausgewählt.');

            return $this->redirectToRoute('admin_clan_member', ['uuid' => $clan->getUuid()]);
        }

        switch ($request->request->get('action')) {
            case 'add':
                $this->memberAdd($clan, $user);
                break;
            case 'kick':
                $this->memberRemove($clan, $user);
                break;
            case 'promote':
                $this->setUserAdmin($clan, $user, true);
                break;
            case 'demote':
                $this->setUserAdmin($clan, $user, false);
                break;
            default:
                throw $this->createNotFoundException('User supplied in POST not found or invalid');
        }

        return $this->redirectToRoute('admin_clan_member', ['uuid' => $clan->getUuid()]);
    }

    private function memberAdd(Clan $clan, User $user): void
    {
        try {
            $clan->getUsers()[] = $user;
            $this->im->persist($clan);
            $this->im->flush();
            $this->addFlash('info', "User {$user->getNickname()} erfolgreich zum Clan hinzugefügt!");
        } catch (PersistException $e) {
            $this->addFlash('error', 'Es ist ein unerwarteter Fehler aufgetreten');
        }
    }

    private function memberRemove(Clan $clan, User $user): void
    {
        try {
            $clan->removeUser($user);
            $this->im->persist($clan);
            $this->im->flush();
            $this->addFlash('info', "User {$user->getNickname()} erfolgreich vom Clan entfernt!");
        } catch (PersistException $e) {
            $this->addFlash('error', 'Es ist ein unerwarteter Fehler aufgetreten');
        }
    }

    private function setUserAdmin(Clan $clan, User $user, bool $admin): void
    {
        try {
            if ($admin) {
                $clan->addAdmin($user);
            } else {
                $clan->removeAdmin($user);
            }
            $this->im->flush();
            $this->addFlash('success', 'Userstatus erfolgreich geändert!');
        } catch (PersistException $e) {
            $this->addFlash('error', 'Es ist ein unerwarteter Fehler aufgetreten');
        }
    }

    /**
     * @Route("/clan/{uuid}/delete", name="clan_delete", methods={"POST"})
     */
    public function delete(string $uuid, Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $clan = $this->clanRepo->findOneById($uuid);
        if (is_null($clan)) {
            throw $this->createNotFoundException();
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_DELETE, $token)) {
            throw $this->createAccessDeniedException('The CSRF token is invalid.');
        }

        try {
            $this->im->remove($clan);
            $this->im->flush();
            $this->addFlash('info', 'Clan erfolgreich gelöscht!');
        } catch (PersistException $e) {
            $this->addFlash('error', 'Es ist ein unerwarteter Fehler aufgetreten');
        }

        return $this->redirectToRoute('admin_clan');
    }
}
