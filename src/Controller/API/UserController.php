<?php

namespace App\Controller\API;

use App\Entity\User;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Service\UserService;
use App\Transfer\Error;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/users", name="users")
 */
class UserController extends AbstractController
{
    private readonly IdmRepository $userRepo;
    private readonly UserService $userService;

    public function __construct(IdmManager $manager, UserService $userService)
    {
        $this->userRepo = $manager->getRepository(User::class);
        $this->userService = $userService;
    }

    /**
     * @Route("", name="", methods={"GET"})
     */
    public function search(Request $request): Response
    {
        $search = $request->query->get('q', '');
        $limit = $request->query->getInt('limit', 10);
        $page = $request->query->getInt('page', 1);
        $sort = $request->query->all('sort');

        try {
            $lazyLoadingCollection = $this->userRepo->findFuzzy($search, $sort);
        } catch (InvalidArgumentException) {
            return new JsonResponse(Error::withMessage('Invalid sort parameter'), Response::HTTP_BAD_REQUEST);
        }

        $items = $lazyLoadingCollection->getPage($page, $limit);

        $result = [];
        $result['count'] = count($items);
        $result['total'] = $lazyLoadingCollection->count();
        $result['items'] = array_map(fn(User $user) => $this->userService->user2Array($user), $items);

        return new JsonResponse(json_encode($result, JSON_THROW_ON_ERROR), Response::HTTP_OK, [], true);
    }
}
