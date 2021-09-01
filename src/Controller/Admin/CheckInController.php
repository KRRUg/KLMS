<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Exception\GamerLifecycleException;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Repository\UserGamerRepository;
use App\Service\GamerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_ADMIN_CHECKIN")
 * @Route("/checkin", name="checkin")
 */
class CheckInController extends AbstractController
{
    private IdmRepository $userRepo;
    private EntityManagerInterface $em;
    private GamerService $gamerService;
    private UserGamerRepository $userGamerRepository;

    public function __construct(EntityManagerInterface $em, GamerService $gamerService, IdmManager $manager, UserGamerRepository $userGamerRepository)
    {
        $this->em = $em;
        $this->userRepo = $manager->getRepository(User::class);
        $this->userGamerRepository = $userGamerRepository;
        $this->gamerService = $gamerService;
    }

    /**
     * @Route("", name="", methods={"GET"})
     */
    public function index()
    {
        return $this->render('admin/check_in/index.html.twig');

    }

    /**
     * @Route("/{uuid}", name="_show", methods={"GET", "POST"})
     */
    public function show(string $uuid, Request $request)
    {
        $user = $this->userRepo->findOneById($uuid);

        if (empty($user)) {
            throw $this->createNotFoundException('User not found');
        }

        $gamer = $this->userGamerRepository->findByUser($user);

        if($gamer->hasPayed()) {
            //checkOut
            $fb = $this->createFormBuilder()
                ->add('action', HiddenType::class, [
                    'data' => 'checkout'
                ]);

            $fb->setAction($this->generateUrl('admin_checkin_show', ['uuid' => $user->getUuid()]));


            $form = $fb->getForm();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $action = $form->get('action')->getData();

                if($action == 'checkout') {
                    $this->gamerService->gamerUnPay($user);
                    $this->addFlash('success', $user->getNickname() . ' erfolgreich ausgechecked!');
                    return $this->redirectToRoute('admin_checkin');
                }
                throw new GamerLifecycleException($user, 'User didn\'t checkedIn yet!');
            }

            return $this->render('admin/check_in/check_out.html.twig', [
                'user' => $user,
                'form' =>  $form->createView(),
            ]);
        } else {
            //checkIn
            $fb = $this->createFormBuilder()
                ->add('action', HiddenType::class, [
                    'data' => 'checkin'
                ]);

            $fb->setAction($this->generateUrl('admin_checkin_show', ['uuid' => $user->getUuid()]));

            $form = $fb->getForm();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $action = $form->get('action')->getData();

                if($action == 'checkin') {
                    $this->gamerService->gamerPay($user);
                    $this->addFlash('success', $user->getNickname() . ' erfolgreich eingechecked!');
                    return $this->redirectToRoute('admin_checkin');
                }
                throw new GamerLifecycleException($user, 'User checkedIn already!');
            }

            return $this->render('admin/check_in/check_in.html.twig', [
                'user' => $user,
                'form' =>  $form->createView(),
            ]);
        }
    }
}
