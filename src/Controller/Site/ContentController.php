<?php

namespace App\Controller\Site;

use App\Entity\Content;
use App\Repository\ContentRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ContentController extends AbstractController
{
    #[Route(path: '/content', name: 'content_index')]
    public function index(ContentRepository $repository): Response
    {
        $content = $repository->findAll();

        return $this->render('site/content/overview.html.twig', [
            'content' => $content,
        ]);
    }

    #[Route(path: '/content/{id}', requirements: ['id' => '\d+'], name: 'content')]
    public function byId(Content $content): Response
    {
        if (!empty($content->getAlias())) {
            return $this->redirectToRoute('content_slug', ['slug' => $content->getAlias()]);
        }

        return $this->render('site/content/index.html.twig', [
            'content' => $content,
            'metaDescription' => $this->buildMetaDescription($content),
            'metaTitle' => $content->getTitle(),
            'canonicalUrl' => $this->generateUrl('content', ['id' => $content->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }

    #[Route(path: '/content/{slug}', requirements: ['slug' => '[a-z]+'], name: 'content_slug')]
    public function bySlug(#[MapEntity(mapping: ['slug' => 'alias'])] Content $content): Response
    {
        return $this->render('site/content/index.html.twig', [
            'content' => $content,
            'metaDescription' => $this->buildMetaDescription($content),
            'metaTitle' => $content->getTitle(),
            'canonicalUrl' => $this->generateUrl('content_slug', ['slug' => $content->getAlias()], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }

    private function buildMetaDescription(Content $content): ?string
    {
        if (!empty($content->getDescription())) {
            return $content->getDescription();
        }

        $raw = strip_tags($content->getContent() ?? '');
        $normalized = trim(preg_replace('/\s+/', ' ', $raw));

        if ($normalized === '') {
            return null;
        }

        if (mb_strlen($normalized) <= 160) {
            return $normalized;
        }

        return rtrim(mb_substr($normalized, 0, 157)) . '…';
    }
}
