<?php

namespace App\Controller\Site;

use App\Entity\Content;
use App\Repository\ContentRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ContentController extends AbstractController
{
    /**
     * @Route("/content", name="content_index")
     */
    public function index(ContentRepository $repository): \Symfony\Component\HttpFoundation\Response
    {
        $content = $repository->findAll();

        return $this->render('site/content/overview.html.twig', [
            'content' => $content,
        ]);
    }

    /**
     * @Route("/content/{id}", requirements={"id"="\d+"}, name="content")
     */
    public function byId(Content $content): \Symfony\Component\HttpFoundation\Response
    {
        if (!empty($content->getAlias())) {
            return $this->redirectToRoute('content_slug', ['slug' => $content->getAlias()]);
        }

        return $this->render('site/content/index.html.twig', [
            'content' => $content,
        ]);
    }

    /**
     * @Route("/content/{slug}", requirements={"slug"="[a-z]+"}, name="content_slug")
     * @ParamConverter("content", options={"mapping": {"slug": "alias"}})
     */
    public function bySlug(Content $content): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('site/content/index.html.twig', [
            'content' => $content,
        ]);
    }
}
