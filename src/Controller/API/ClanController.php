<?php

namespace App\Controller\API;

use App\Entity\Clan;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/clans", name="clans")
 */
class ClanController extends AbstractController
{
    private readonly IdmRepository $clanRepo;

    public function __construct(IdmManager $manager)
    {
        $this->clanRepo = $manager->getRepository(Clan::class);
    }

    /**
     * @Route("", name="", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function search(Request $request): Response
    {
        $search = $request->query->get('q', '');
        $limit = $request->query->getInt('limit', 10);
        $page = $request->query->getInt('page', 1);

        $lazyLoadingCollection = $this->clanRepo->findFuzzy($search);
        $items = $lazyLoadingCollection->getPage($page, $limit);

        $result = [];
        $result['count'] = is_countable($items) ? count($items) : 0;
        $result['total'] = $lazyLoadingCollection->count();
        // TODO this is hacky, but required for front-end JS a.t.m.?
        $result['items'] = array_map(fn (Clan $clan) => [
            'uuid' => $clan->getUuid(),
            'name' => $clan->getName(),
            'clantag' => $clan->getClantag(),
        ], $items);

        return new JsonResponse(json_encode($result, JSON_THROW_ON_ERROR), Response::HTTP_OK, [], true);
    }
}
