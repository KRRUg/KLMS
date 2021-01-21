<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
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
class UserController extends AbstractController
{
    private IdmManager $manager;
    private IdmRepository $userRepo;

    public function __construct(IdmManager $manager)
    {
        $this->manager = $manager;
        $this->userRepo = $manager->getRepository(User::class);
    }

    /**
     * @Route("/user", name="user", methods={"GET"})
     */
    public function index(Request $request)
    {
        $search = $request->query->get('q', '');
        $limit = $request->query->getInt('limit', 10);
        $page = $request->query->getInt('page', 1);

        $collection = $this->userRepo->findFuzzy($search);
        $users = $collection->getPage($page, $limit);

        if (empty($users)) {
            throw $this->createNotFoundException();
        }

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
        ]);

    }

    /**
     * @Route("/user/{uuid}", name="user_show", methods={"GET"})
     */
    public function show(string $uuid)
    {
        $user = $this->userRepo->findOneById($uuid);

        if (empty($user)) {
            throw $this->createNotFoundException('User not found');
        }

        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/user/{uuid}/edit", name="user_edit", methods={"GET", "POST"})
     */
    public function edit(string $uuid, Request $request)
    {
        $user = $this->userRepo->findOneById($uuid);

        if (empty($user)) {
            throw $this->createNotFoundException('User not found');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $user = $form->getData();
                $this->manager->persist($user);
                $this->manager->flush();
                $this->addFlash('success', 'User erfolgreich bearbeitet!');
                return $this->redirectToRoute('admin_user');
            } catch (PersistException $e) {
                switch ($e->getCode()) {
                    case PersistException::REASON_NON_UNIQUE:
                        $this->addFlash('error', 'Nickname und/oder Email ist schon in Verwendung');
                        break;
                    default:
                        $this->addFlash('error', 'Es ist ein unerwarteter Fehler aufgetreten');
                        break;
                }
            }
        }

        return $this->render('admin/user/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
