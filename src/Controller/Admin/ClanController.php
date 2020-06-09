<?php

namespace App\Controller\Admin;

use App\Form\Admin\AdminClanEditType;
use App\Model\ClanModel;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Annotation\Route;

class ClanController extends AbstractController
{
    //TODO: Change ClanController and UserService to only require Clan UUID where necessary to reduce IDM Calls

    /**
     * @var UserService
     */
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @Route("/clan", name="clan", methods={"GET"})
     */
    public function index()
    {
        $clans = $this->userService->getAllClans();
        return $this->render('admin/clan/index.html.twig', [
            'clans' => $clans,
        ]);
    }

    /**
     * @Route("/clan/{uuid}/edit", name="clan_edit", methods={"GET", "POST"})
     */
    public function edit(string $uuid, Request $request, FlashBagInterface $flashBag)
    {
        $clan = $this->userService->getClan($uuid);

        $form = $this->createForm(AdminClanEditType::class, $clan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // get Data from Form

            /* @var ClanModel */
            $clanform = $form->getData();

            if (!$this->userService->checkClanAvailability($clanform->getName(), 'clanname') && $clanform->getName() !== $clan->getName()) {
                $form->get('name')->addError(new FormError('Clanname wird bereits benutzt!'));

                return $this->render('admin/clan/edit.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            if (!$this->userService->checkClanAvailability($clanform->getClantag(), 'clantag') && $clanform->getClantag() !== $clan->getClantag()) {
                $form->get('name')->addError(new FormError('Clantag wird bereits benutzt!'));

                return $this->render('admin/clan/edit.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            foreach ($clanform->getUsers() as $user) {
                if(in_array($user->getUser()->getUuid(), $form->get('admins')->getData())) {
                    $user->setAdmin(true);
                } else {
                    $user->setAdmin(false);
                }
            }

            if ($this->userService->editClan($clanform)) {

                $flashBag->add('info', 'Clan erfolgreich bearbeitet!');

                return $this->redirectToRoute('admin_clan');
            } else {

                $flashBag->add('error', 'Es ist ein unerwarteter Fehler aufgetreten');

                return $this->redirectToRoute('admin_clan_edit', ['uuid' => $uuid]);
            }

        }

        return $this->render('admin/clan/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/clan/{uuid}", name="clan_show", methods={"GET"})
     */
    public function show(string $uuid)
    {
        $clan = $this->userService->getClan($uuid);

        return $this->render('admin/clan/show.html.twig', [
            'clan' => $clan,
        ]);
    }


    /**
     * @Route("/clan/{uuid}", name="clan_delete", methods={"DELETE"})
     */
    public function delete(string $uuid, FlashBagInterface $flashBag)
    {
        //TODO: Move to AJAX Modal and implement CSRF Token Protection

        $clan = $this->userService->getClan($uuid);

        if(!$clan) {
            return $this->createNotFoundException('Clan not found');
        }

        if ($this->userService->deleteClan($clan)) {
            $flashBag->add('info', 'Clan erfolgreich gelöscht!');
        } else {
            $flashBag->add('error', 'Es ist ein unerwarteter Fehler aufgetreten');
        }

        return $this->redirectToRoute('admin_clan');

    }

    /**
     * @Route("/clan/{uuid}/member", name="clan_member_show", methods={"GET"})
     */
    public function showMember(string $uuid)
    {
        $clan = $this->userService->getClan($uuid);

        return $this->render('admin/clan/member_show.html.twig', [
            'clan' => $clan,
        ]);
    }

    /**
     * @Route("/clan/{uuid}/member/add", name="clan_member_add", methods={"POST"})
     */
    public function addMember(string $uuid, Request $request, FlashBagInterface $flashBag)
    {
        $clan = $this->userService->getClan($uuid);

        if(empty($request->request->get('user_uuid'))) {
            $this->createNotFoundException('No User supplied in POST (user_uuid)');
        }

        $user = $this->userService->getUser($request->request->get('user_uuid'));

        if(!$user) {
            $this->createNotFoundException('User supplied in POST not found or invalid');
        }

        $nickname = $user->getNickname();

        if ($this->userService->addClanMember($clan, array($user))) {
            $flashBag->add('info', "User {$nickname} erfolgreich zum Clan hinzugefügt!");
        } else {
            $flashBag->add('error', 'Es ist ein unerwarteter Fehler aufgetreten');
        }

        return $this->redirectToRoute('admin_clan_member_show', ['uuid' => $clan->getUuid()]);
    }

    /**
     * @Route("/clan/{uuid}/member/remove", name="clan_member_remove", methods={"POST"})
     */
    public function removeMember(string $uuid, Request $request, FlashBagInterface $flashBag)
    {
        $clan = $this->userService->getClan($uuid);

        if(empty($request->request->get('user_uuid'))) {
            $this->createNotFoundException('No User supplied in POST (user_uuid)');
        }

        $user = $this->userService->getUser($request->request->get('user_uuid'));

        if(!$user) {
            $this->createNotFoundException('User supplied in POST not found or invalid');
        }

        $nickname = $user->getNickname();

        if ($this->userService->removeClanMember($clan, array($user))) {
            $flashBag->add('info', "User {$nickname} erfolgreich aus dem Clan entfernt!");
        } else {
            $flashBag->add('error', 'Es ist ein unerwarteter Fehler aufgetreten');
        }

        return $this->redirectToRoute('admin_clan_member_show', ['uuid' => $clan->getUuid()]);
    }

}
