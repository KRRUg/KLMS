<?php


namespace App\Controller\API;

use App\Entity\User;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Service\GamerService;
use App\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @IsGranted("ROLE_ADMIN")
 * @Route("/gamers", name="gamers")
 */
class GamerController extends AbstractController
{
    private IdmRepository $userRepo;
    private UserService $userService;
    private GamerService $gamerService;

    public function __construct(IdmManager $manager, UserService $userService, GamerService $gamerService)
    {
        $this->userRepo = $manager->getRepository(User::class);
        $this->gamerService = $gamerService;
        $this->userService = $userService;
    }

    /**
     * @IsGranted("ROLE_ADMIN_PAYMENT")
     * @Route("/registered", name="_registered", methods={"GET"})
     */
    public function registered(Request $request)
    {
        $search = $request->query->get('q', '');
        $limit = $request->query->getInt('limit', 10);
        $page = $request->query->getInt('page', 1);
        $sort = $request->query->get('sort', []);

        $items = $this->gamerService->getRegisteredGamerWithStatus();

        $items = array_map(function (array $userStatus) {return [ 'user' => $this->userService->user2Array($userStatus['user']), 'status' => $this->gamerService->gamer2Array($userStatus['status']) ]; }, $items);

        $result = array();
        $result['count'] = count($items);
        //Workaround until we have real Pagination
        $result['total'] = count($items);
        $result['items'] = $items;

        return new JsonResponse(json_encode($result), 200, [], true);
    }

    /**
     * @IsGranted("ROLE_ADMIN_CHECKIN")
     * @Route("/paid", name="_paid", methods={"GET"})
     */
    public function paid(Request $request)
    {
        $search = $request->query->get('q', '');
        $limit = $request->query->getInt('limit', 10);
        $page = $request->query->getInt('page', 1);
        $sort = $request->query->get('sort', []);

        $items = $this->gamerService->getPaidGamersWithStatus();

        $items = array_map(function (array $userStatus) {return [ 'user' => $this->userService->user2Array($userStatus['user']), 'status' => $this->gamerService->gamer2Array($userStatus['status']) ]; }, $items);

        $result = array();
        $result['count'] = count($items);
        //Workaround until we have real Pagination
        $result['total'] = count($items);
        $result['items'] = $items;

        return new JsonResponse(json_encode($result), 200, [], true);
    }
}