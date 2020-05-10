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

            if(!$this->userService->checkClanAvailability($clanform->getName(), 'clanname') && $clanform->getName() !== $clan->getName()) {
                $form->get('name')->addError(new FormError('Clanname wird bereits benutzt!'));

                return $this->render('admin/clan/edit.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            if(!$this->userService->checkClanAvailability($clanform->getClantag(), 'clantag') && $clanform->getClantag() !== $clan->getClantag()) {
                $form->get('name')->addError(new FormError('Clantag wird bereits benutzt!'));

                return $this->render('admin/clan/edit.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            if($this->userService->editClan($clanform)) {

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
    public function delete(string $uuid, Request $request, FlashBagInterface $flashBag)
    {
        $clan = $this->userService->getClan($uuid);

        $form = $this->createForm(AdminClanEditType::class, $clan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // get Data from Form

            /* @var ClanModel */
            $clanform = $form->getData();

            if(!$this->userService->checkClanAvailability($clanform->getName(), 'clanname') && $clanform->getName() !== $clan->getName()) {
                $form->get('nickname')->addError(new FormError('Clanname wird bereits benutzt!'));

                return $this->render('admin/clan/edit.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            if(!$this->userService->checkClanAvailability($clanform->getName(), 'clanname') && $clanform->getName() !== $clan->getName()) {
                $form->get('nickname')->addError(new FormError('Clanname wird bereits benutzt!'));

                return $this->render('admin/clan/edit.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            if($this->userService->editUser($clanform)) {

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
}
