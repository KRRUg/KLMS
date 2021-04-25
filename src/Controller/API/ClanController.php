<?php


namespace App\Controller\API;

use App\Entity\Clan;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
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
    private IdmRepository $clanRepo;

    public function __construct(IdmManager $manager)
    {
        $this->clanRepo = $manager->getRepository(Clan::class);
    }

    /**
     * @Route("", name="", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request)
    {
        $search = $request->query->get('q', '');
        $limit = $request->query->getInt('limit', 10);
        $page = $request->query->getInt('page', 1);

        $lazyLoadingCollection = $this->clanRepo->findFuzzy($search);
        $items = $lazyLoadingCollection->getPage($page, $limit);

        $result = array();
        $result['count'] = count($items);
        $result['total'] = $lazyLoadingCollection->count();
        $result['items'] = array_map(function (Clan $clan) {
            // TODO this is hacky, but required for front-end JS a.t.m.?
            return [
                'uuid' => $clan->getUuid(),
                'name' => $clan->getName(),
                'clantag' => $clan->getClantag(),
            ];
        }, $items);

        return new JsonResponse(json_encode($result), 200, [], true);
    }
}