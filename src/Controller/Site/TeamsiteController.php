<?php

namespace App\Controller\Site;

use App\Entity\Teamsite;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class TeamsiteController extends AbstractController
{
    /**
     * @Route("/teamsite/{id}", name="teamsite")
     * @ParamConverter()
     */
    public function byId(Teamsite $teamsite)
    {
        return $this->render('site/teamsite/index.html.twig', [
            'teamsite' => $teamsite,
        ]);
    }
}