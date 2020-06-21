<?php

namespace App\Controller\Site;

use App\Exception\UserServiceException;
use App\Form\ClanCreateType;
use App\Form\ClanEditType;
use App\Form\ClanJoinType;
use App\Model\ClanModel;
use App\Service\UserService;
use App\Transfer\ClanCreateTransfer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ClanController extends AbstractController
{
    //TODO: Change ClanController and UserService to only require Clan UUID where necessary to reduce IDM Calls
    //TODO: Better Exception/Error Handling see https://github.com/KRRUg/KLMS/blob/feature/admin-mgmt/src/Controller/BaseController.php and Admin/PermissionController.php

    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @Route("/clan", name="clan", methods={"GET"})
     */
    public function index()
    {
        $clans = $this->userService->queryClans(null, null, 999999, false);

        return $this->render('site/clan/list.html.twig', [
            'clans' => $clans,
        ]);
    }

    /**
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     * @Route("/clan/my", name="clan_my", methods={"GET"})
     */
    public function my()
    {
        $user = $this->userService->getUsersInfoByUuid([$this->getUser()->getUser()->getUuid()]);

        return $this->render('site/clan/myClans.html.twig', [
            'user' => $user[0],
        ]);
    }

    /**
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     * @Route("/clan/join", name="clan_join", methods={"GET", "POST"})
     */
    public function join(Request $request, FlashBagInterface $flashBag)
    {
        $data = [];
        if ($request->query->get('uuid')) {
            $clan = $this->userService->getClan($request->query->get('uuid'), true);
            if ($clan) {
                $data = [$clan->getName() => $request->query->get('uuid')];
            }
        }

        $form = $this->createForm(ClanJoinType::class, $data, [
            'data-remote-target' => $this->generateUrl('api_clans'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // get Data from Form

            /* @var ClanCreateTransfer */
            $clanform = $form->getData();
            $clan = $this->userService->getClan($clanform['name']);

            try {
                $this->userService->addClanMember($clan, [$this->getUser()->getUser()], $clanform['joinPassword']);
            } catch (UserServiceException $e) {
                $form = $this->createForm(ClanJoinType::class, null, [
                    'data-remote-target' => $this->generateUrl('api_clans'),
                ]);
                $form->get('joinPassword')->addError(new FormError('Das angegebene JoinPasswort ist falsch!'));

                return $this->render('site/clan/join.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $flashBag->add('info', 'Clan erfolgreich beigetreten!');

            return $this->redirectToRoute('clan_show', ['uuid' => $clan->getUuid()]);
        }

        return $this->render('site/clan/join.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     * @Route("/clan/create", name="clan_create", methods={"GET", "POST"})
     *
     * @return RedirectResponse|Response
     */
    public function create(Request $request, FlashBagInterface $flashBag)
    {
        $form = $this->createForm(ClanCreateType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // get Data from Form

            /* @var ClanCreateTransfer */
            $clanform = $form->getData();

            $clanform->user = $this->getUser()->getUser()->getUuid();

            if (!$this->userService->checkClanAvailability($clanform->name, 'clanname')) {
                $form->get('name')->addError(new FormError('Clanname wird bereits benutzt!'));

                return $this->render('site/clan/create.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            if (!$this->userService->checkClanAvailability($clanform->clantag, 'clantag')) {
                $form->get('clantag')->addError(new FormError('Clantag wird bereits benutzt!'));

                return $this->render('site/clan/create.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $response = $this->userService->createClan($clanform);
            if ($response) {
                $flashBag->add('info', 'Clan erfolgreich angelegt!');

                return $this->redirectToRoute('clan_show', ['uuid' => $response->getUuid()]);
            } else {
                $flashBag->add('error', 'Es ist ein unerwarteter Fehler aufgetreten');

                return $this->render('site/clan/create.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
        }

        return $this->render('site/clan/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     * @Route("/clan/{uuid}/edit", name="clan_edit", methods={"GET", "POST"})
     *
     * @return AccessDeniedException|RedirectResponse|Response
     */
    public function edit(string $uuid, Request $request, FlashBagInterface $flashBag)
    {
        $clan = $this->userService->getClan($uuid, true);

        $admins = [];

        foreach ($clan->getUsers() as $user) {
            if ($user->getAdmin()) {
                $admins[] = $user->getUser()->getUuid();
            }
        }

        // Check if User is Admin of the Clan otherwise throw Forbidden
        if (!in_array($this->getUser()->getUser()->getUuid(), $admins)) {
            return $this->createAccessDeniedException('Nur Admins können den Clan bearbeiten!');
        }

        $form = $this->createForm(ClanEditType::class, $clan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // get Data from Form

            /* @var ClanModel */
            $clanform = $form->getData();

            if (!$this->userService->checkClanAvailability($clanform->getName(), 'clanname') && $clanform->getName() !== $clan->getName()) {
                $form->get('name')->addError(new FormError('Clanname wird bereits benutzt!'));

                return $this->render('site/clan/edit.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            if (!$this->userService->checkClanAvailability($clanform->getClantag(), 'clantag') && $clanform->getClantag() !== $clan->getClantag()) {
                $form->get('clantag')->addError(new FormError('Clantag wird bereits benutzt!'));

                return $this->render('site/clan/edit.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            foreach ($clanform->getUsers() as $user) {
                if (in_array($user->getUser()->getUuid(), $form->get('admins')->getData())) {
                    $user->setAdmin(true);
                } else {
                    $user->setAdmin(false);
                }
            }

            if ($this->userService->editClan($clanform)) {
                $flashBag->add('info', 'Clan erfolgreich bearbeitet!');

                return $this->redirectToRoute('clan_show', ['uuid' => $uuid]);
            } else {
                $flashBag->add('error', 'Es ist ein unerwarteter Fehler aufgetreten');

                return $this->redirectToRoute('clan_edit', ['uuid' => $uuid]);
            }
        }

        return $this->render('site/clan/edit.html.twig', [
            'form' => $form->createView(),
            'clan' => $clan,
        ]);
    }

    /**
     * @Route("/clan/{uuid}", name="clan_show", methods={"GET"})
     *
     * @return Response
     */
    public function show(string $uuid)
    {
        $clan = $this->userService->getClan($uuid, true);

        $isClanAdmin = false;

        foreach ($clan->getUsers() as $user) {
            if ($user->getUser()->getUuid() === $this->getUser()->getUser()->getUuid()) {
                if ($user->getAdmin()) {
                    $isClanAdmin = true;
                    break;
                }
            }
        }

        return $this->render('site/clan/show.html.twig', [
            'clan' => $clan,
            'isClanAdmin' => $isClanAdmin,
        ]);
    }

    /**
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     * @Route("/clan/{uuid}", name="clan_delete", methods={"DELETE"})
     *
     * @return AccessDeniedException|RedirectResponse|NotFoundHttpException
     */
    public function delete(string $uuid, FlashBagInterface $flashBag)
    {
        //TODO: Implement "TrashBin" where the Clan gets only set to inactive/deleted and is not actually deleted
        //TODO: Move to AJAX Modal and implement CSRF Token Protection

        $clan = $this->userService->getClan($uuid, true);

        if (!$clan) {
            return $this->createNotFoundException('Clan not found');
        }

        $admins = [];

        foreach ($clan->getUsers() as $user) {
            if ($user->getAdmin()) {
                $admins[] = $user->getUser()->getUuid();
            }
        }

        // Check if User is Admin of the Clan otherwise throw Forbidden
        if (!in_array($this->getUser()->getUser()->getUuid(), $admins)) {
            return $this->createAccessDeniedException('Nur Admins können den Clan bearbeiten!');
        }

        if ($this->userService->deleteClan($clan)) {
            $flashBag->add('info', 'Clan erfolgreich gelöscht!');
        } else {
            $flashBag->add('error', 'Es ist ein unerwarteter Fehler aufgetreten');
        }

        return $this->redirectToRoute('user_profile');
    }

    /**
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     * @Route("/clan/{uuid}/member/remove", name="clan_member_remove", methods={"POST"})
     *
     * @return AccessDeniedException|NotFoundHttpException|RedirectResponse
     */
    public function removeMember(string $uuid, Request $request, FlashBagInterface $flashBag)
    {
        $clan = $this->userService->getClan($uuid, true);

        if (!$clan) {
            return $this->createNotFoundException('Clan not found');
        }

        $admins = [];

        foreach ($clan->getUsers() as $user) {
            if ($user->getAdmin()) {
                $admins[] = $user->getUser()->getUuid();
            }
        }

        // Check if User is Admin of the Clan otherwise throw Forbidden
        if (!in_array($this->getUser()->getUser()->getUuid(), $admins)) {
            return $this->createAccessDeniedException('Nur Admins können den Clan bearbeiten!');
        }

        if (empty($request->request->get('user_uuid'))) {
            $this->createNotFoundException('No User supplied in POST (user_uuid)');
        }

        $user = $this->userService->getUser($request->request->get('user_uuid'));

        if (!$user) {
            $this->createNotFoundException('User supplied in POST not found or invalid');
        }

        $nickname = $user->getNickname();

        if ($this->userService->removeClanMember($clan, [$user], false)) {
            $flashBag->add('info', "User {$nickname} erfolgreich aus dem Clan entfernt!");
        } else {
            $flashBag->add('error', 'Es ist ein unerwarteter Fehler aufgetreten');
        }

        return $this->redirectToRoute('clan_edit', ['uuid' => $clan->getUuid()]);
    }
}
