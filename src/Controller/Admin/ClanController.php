<?php

namespace App\Controller\Admin;

use App\Entity\Clan;
use App\Entity\User;
use App\Form\ClanType;
use App\Idm\Exception\PersistException;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_ADMIN_USER")
 */
class ClanController extends AbstractController
{
    //TODO: Better Exception/Error Handling see https://github.com/KRRUg/KLMS/blob/feature/admin-mgmt/src/Controller/BaseController.php and Admin/PermissionController.php
    private const CSRF_TOKEN_DELETE = "clanDeleteToken";
    private const CSRF_TOKEN_MEMBER_ADD = "clanMemberAddToken";
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
        return $this->render('admin/clan/index.html.twig', [
            'clans' => $clans,
            'csrf_token_delete' => self::CSRF_TOKEN_DELETE,
        ]);
    }

    /**
     * @Route("/clan/create", name="clan_create", methods={"GET", "POST"})
     */
    public function create(Request $request)
    {
        $form = $this->createForm(ClanType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $clan = $form->getData();
            try{
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
    public function edit(string $uuid, Request $request)
    {
        $clan = $this->clanRepo->findOneById($uuid);
        if (is_null($clan)){
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
     * @Route("/clan/{uuid}/member", name="clan_member", methods={"GET", "POST"})
     */
    public function member(string $uuid)
    {
        $clan = $this->clanRepo->findOneById($uuid);
        if (is_null($clan)){
            throw $this->createNotFoundException();
        }

        return $this->render('admin/clan/member.html.twig', [
            'clan' => $clan,
            'csrf_token_member_add' => self::CSRF_TOKEN_MEMBER_ADD,
            'csrf_token_member_remove' => self::CSRF_TOKEN_MEMBER_REMOVE,
        ]);
    }

    /**
     * @Route("/clan/{uuid}", name="clan_show", methods={"GET"})
     */
    public function show(string $uuid)
    {
        $clan = $this->clanRepo->findOneById($uuid);
        if (is_null($clan)){
            throw $this->createNotFoundException();
        }

        return $this->render('admin/clan/show.html.twig', [
            'clan' => $clan,
        ]);
    }

    /**
     * @Route("/clan/{uuid}/delete", name="clan_delete", methods={"POST"})
     */
    public function delete(string $uuid, Request $request)
    {
        $clan = $this->clanRepo->findOneById($uuid);
        if (is_null($clan)){
            throw $this->createNotFoundException();
        }

        $token = $request->request->get('_token');
        if(!$this->isCsrfTokenValid(self::CSRF_TOKEN_DELETE, $token)) {
            $this->addFlash('error', 'The CSRF token is invalid.');
            return $this->redirectToRoute('admin_clan');
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

    /**
     * @Route("/clan/{uuid}/member/add", name="clan_member_add", methods={"POST"})
     */
    public function addMember(string $uuid, Request $request)
    {
        $token = $request->request->get('_token');
        if(!$this->isCsrfTokenValid(self::CSRF_TOKEN_MEMBER_ADD, $token)) {
            $this->addFlash('error', 'The CSRF token is invalid.');
            return $this->redirectToRoute('admin_clan');
        }

        $clan = $this->clanRepo->findOneById($uuid);
        $user = $this->userRepo->findOneById($request->request->get('user_uuid'));

        if(empty($clan)) {
            $this->createNotFoundException();
        }
        if(!$user) {
            $this->createNotFoundException('User supplied in POST not found or invalid');
        }

        $nickname = $user->getNickname();

        try {
            $clan->getUsers()[] = $user;
            $this->im->persist($clan);
            $this->im->flush();
            $this->addFlash('info', "User {$nickname} erfolgreich zum Clan hinzugefügt!");
        } catch (PersistException $e) {
            $this->addFlash('error', 'Es ist ein unerwarteter Fehler aufgetreten');
        }

        return $this->redirectToRoute('admin_clan_member', ['uuid' => $clan->getUuid()]);
    }

    /**
     * @Route("/clan/{uuid}/member/remove", name="clan_member_remove", methods={"POST"})
     */
    public function removeMember(string $uuid, Request $request)
    {
        $token = $request->request->get('_token');
        if(!$this->isCsrfTokenValid(self::CSRF_TOKEN_MEMBER_REMOVE, $token)) {
            $this->addFlash('error', 'The CSRF token is invalid.');
            return $this->redirectToRoute('admin_clan');
        }

        $clan = $this->clanRepo->findOneById($uuid);
        $user = $this->userRepo->findOneById($request->request->get('user_uuid'));

        if(empty($clan)) {
            $this->createNotFoundException();
        }
        if(!$user) {
            $this->createNotFoundException('User supplied in POST not found or invalid');
        }

        $nickname = $user->getNickname();

        try {
            $clan->removeUser($user);
            $this->im->persist($clan);
            $this->im->flush();
            $this->addFlash('info', "User {$nickname} erfolgreich aus dem Clan entfernt!");
        } catch (PersistException $e) {
            $this->addFlash('error', 'Es ist ein unerwarteter Fehler aufgetreten');
        }

        return $this->redirectToRoute('admin_clan_member', ['uuid' => $clan->getUuid()]);
    }
}
