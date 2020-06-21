<?php


namespace App\Controller\API;

use App\Model\ClanModel;
use App\Service\UserService;
use App\Transfer\Error;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/clans", name="clans")
 */
class ClanController extends AbstractController
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @Route("", name="", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request)
    {
        $search = $request->query->get('q');
        $limit = $request->query->getInt('limit', 10);
        $page = $request->query->getInt('page', 1);

        $ret = $this->userService->queryClans($search, $page, $limit);

        if (empty($ret)) {
            return new JsonResponse(Error::withMessage("Not Found"), 404);
        }

        $ret->items = array_map(function (ClanModel $clan) {
            return [
                'uuid' => $clan->getUuid(),
                'nickname' => $clan->getName(),
                'firstname' => $clan->getClantag(),
                'surname' => '',
            ];
        }, $ret->items);


        return new JsonResponse(json_encode($ret), 200, [], true);
    }
}