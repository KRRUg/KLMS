<?php


namespace App\Controller\API;

use App\Entity\Clan;
use App\Entity\User;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Service\UserService;
use App\Transfer\Error;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/users", name="users")
 */
class UserController extends AbstractController
{
    private IdmRepository $userRepo;
    private UserService $userService;

    public function __construct(IdmManager $manager, UserService $userService)
    {
        $this->userRepo = $manager->getRepository(User::class);
        $this->userService = $userService;
    }

    /**
     * @Route("", name="", methods={"GET"})
     */
    public function search(Request $request)
    {
        $search = $request->query->get('q', '');
        $limit = $request->query->getInt('limit', 10);
        $page = $request->query->getInt('page', 1);
        $sort = $request->query->get('sort', []);

        try{
            $lazyLoadingCollection = $this->userRepo->findFuzzy($search, $sort);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(Error::withMessage("Invalid sort parameter"), 400);
        }

        $items = $lazyLoadingCollection->getPage($page, $limit);

        $result = array();
        $result['count'] = count($items);
        $result['total'] = $lazyLoadingCollection->count();
        $result['items'] = array_map(function (User $user) { return $this->userService->user2Array($user); }, $items);

        return new JsonResponse(json_encode($result), 200, [], true);
    }
}