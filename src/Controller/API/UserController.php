<?php


namespace App\Controller\API;

use App\Entity\Clan;
use App\Entity\User;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
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

    public function __construct(IdmManager $manager)
    {
        $this->userRepo = $manager->getRepository(User::class);
    }

    /**
     * @Route("", name="", methods={"GET"})
     */
    public function search(Request $request)
    {
        $search = $request->query->get('q', '');
        $limit = $request->query->getInt('limit', 10);
        $page = $request->query->getInt('page', 1);

        $lazyLoadingCollection = $this->userRepo->findFuzzy($search);
        $items = $lazyLoadingCollection->getPage($page, $limit);

        if (empty($items)) {
            return new JsonResponse(Error::withMessage("Not Found"), 404);
        }

        $result = array();
        $result['count'] = count($items);
        $result['total'] = $lazyLoadingCollection->count();
        $result['items'] = array_map(function (User $user) {
            return [
                'uuid' => $user->getUuid(),
                'email' => $user->getEmail(),
                'nickname' => $user->getNickname(),
                'firstname' => $user->getFirstname(),
                'surname' => $user->getSurname(),
                'clans' => array_map(function ($clan) {
                    return [
                        'uuid' => $clan->getUuid(),
                        'name' => $clan->getName(),
                        'clantag' => $clan->getClantag(),
                    ];
                }, $user->getClans()->toArray()),
            ];
        }, $items);

        return new JsonResponse(json_encode($result), 200, [], true);
    }
}