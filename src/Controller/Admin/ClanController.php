<?php

namespace App\Controller\Admin;

use App\Form\Admin\AdminClanEditType;
use App\Form\ClanCreateType;
use App\Model\ClanModel;
use App\Service\UserService;
use App\Transfer\ClanCreateTransfer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_ADMIN_USER")
 */
class ClanController extends AbstractController
{
    //TODO: Change ClanController and UserService to only require Clan UUID where necessary to reduce IDM Calls
    //TODO: Better Exception/Error Handling see https://github.com/KRRUg/KLMS/blob/feature/admin-mgmt/src/Controller/BaseController.php and Admin/PermissionController.php

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
        //TODO: implement Client Pagination
        $clans = $this->userService->queryClans(null, null, 999999);
        return $this->render('admin/clan/index.html.twig', [
            'clans' => $clans,
        ]);
    }

    /**
     * @Route("/clan/create", name="clan_create", methods={"GET", "POST"})
     * @param Request $request
     * @param FlashBagInterface $flashBag
     * @return RedirectResponse|Response
     */
    public function create (Request $request, FlashBagInterface $flashBag)
    {

        $form = $this->createForm(ClanCreateType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // get Data from Form

            /* @var ClanCreateTransfer */
            $clanform = $form->getData();

            if (!$this->userService->checkClanAvailability($clanform->name, 'clanname')) {
                $form->get('name')->addError(new FormError('Clanname wird bereits benutzt!'));

                return $this->render('admin/clan/create.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            if (!$this->userService->checkClanAvailability($clanform->clantag, 'clantag')) {
                $form->get('clantag')->addError(new FormError('Clantag wird bereits benutzt!'));

                return $this->render('admin/clan/create.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $response = $this->userService->createClan($clanform);
            if ($response) {

                $flashBag->add('info', 'Clan erfolgreich angelegt!');

                return $this->redirectToRoute('admin_clan_show', ['uuid' => $response->getUuid()]);
            } else {

                $flashBag->add('error', 'Es ist ein unerwarteter Fehler aufgetreten');

                return $this->redirectToRoute('admin_clan');
            }

        }

        return $this->render('admin/clan/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/clan/{uuid}/edit", name="clan_edit", methods={"GET", "POST"})
     * @param string $uuid
     * @param Request $request
     * @param FlashBagInterface $flashBag
     * @return RedirectResponse|Response
     */
    public function edit(string $uuid, Request $request, FlashBagInterface $flashBag)
    {
        $clan = $this->userService->getClan($uuid, true);

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
                $form->get('clantag')->addError(new FormError('Clantag wird bereits benutzt!'));

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
            'clan' => $clan
        ]);
    }

    /**
     * @Route("/clan/{uuid}", name="clan_show", methods={"GET"})
     * @param string $uuid
     * @return Response
     */
    public function show(string $uuid)
    {
        $clan = $this->userService->getClan($uuid, true);

        return $this->render('admin/clan/show.html.twig', [
            'clan' => $clan,
        ]);
    }


    /**
     * @Route("/clan/{uuid}", name="clan_delete", methods={"DELETE"})
     * @param string $uuid
     * @param FlashBagInterface $flashBag
     * @return RedirectResponse|NotFoundHttpException
     */
    public function delete(string $uuid, FlashBagInterface $flashBag)
    {
        //TODO: Move to AJAX Modal and implement CSRF Token Protection

        $clan = $this->userService->getClan($uuid, true);

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
     * @Route("/clan/{uuid}/member/add", name="clan_member_add", methods={"POST"})
     * @param string $uuid
     * @param Request $request
     * @param FlashBagInterface $flashBag
     * @return RedirectResponse
     */
    public function addMember(string $uuid, Request $request, FlashBagInterface $flashBag)
    {
        $clan = $this->userService->getClan($uuid, true);

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

        return $this->redirectToRoute('admin_clan_edit', ['uuid' => $clan->getUuid()]);
    }

    /**
     * @Route("/clan/{uuid}/member/remove", name="clan_member_remove", methods={"POST"})
     * @param string $uuid
     * @param Request $request
     * @param FlashBagInterface $flashBag
     * @return RedirectResponse
     */
    public function removeMember(string $uuid, Request $request, FlashBagInterface $flashBag)
    {
        $clan = $this->userService->getClan($uuid, true);

        if(empty($request->request->get('user_uuid'))) {
            $this->createNotFoundException('No User supplied in POST (user_uuid)');
        }

        $user = $this->userService->getUser($request->request->get('user_uuid'));

        if(!$user) {
            $this->createNotFoundException('User supplied in POST not found or invalid');
        }

        $nickname = $user->getNickname();

        if ($this->userService->removeClanMember($clan, array($user), false)) {
            $flashBag->add('info', "User {$nickname} erfolgreich aus dem Clan entfernt!");
        } else {
            $flashBag->add('error', 'Es ist ein unerwarteter Fehler aufgetreten');
        }

        return $this->redirectToRoute('admin_clan_edit', ['uuid' => $clan->getUuid()]);
    }

}
