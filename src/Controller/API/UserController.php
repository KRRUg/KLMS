<?php


namespace App\Controller\API;

use App\Security\User;
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
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @Route("", name="", methods={"GET"})
     */
    public function search(Request $request)
    {
        $search = $request->query->get('q');
        $limit = $request->query->getInt('limit', 10);
        $page = $request->query->getInt('page', 1);
        $fullInfo = $request->query->getBoolean('fullUser', false);

        $ret = $this->userService->queryUsers($search, $page, $limit);

        if (empty($ret)) {
            return new JsonResponse(Error::withMessage("Not Found"), 404);
        }

        if ($fullInfo) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        } else {
            $ret->items = array_map(function (User $user) {
                return [
                    'email' => $user->getUsername(),
                    'nickname' => $user->getNickname(),
                    'firstname' => $user->getFirstname(),
                    'surname' => $user->getSurname(),
                ];
            }, $ret->items);
        }

        return new JsonResponse(json_encode($ret), 200, [], true);
    }
}