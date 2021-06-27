<?php

namespace App\Twig;

use App\Repository\ContentRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

class ContentExtension extends AbstractExtension
{
    private ContentRepository $repo;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(ContentRepository $repo, UrlGeneratorInterface $urlGenerator)
    {
        $this->repo = $repo;
        $this->urlGenerator = $urlGenerator;
    }

    public function getTests()
    {
        return [
            new TwigTest('slug', [$this, 'slugExists'])
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter('slug_link', [$this, 'slugLink'], ['is_safe' => ['html']])
        ];
    }

    public function slugExists(string $slug): bool
    {
        $content = $this->repo->findBySlug($slug);
        return !empty($content);
    }

    public function slugLink(string $slug): string
    {
        $content = $this->repo->findBySlug($slug);
        if (empty($content)) {
            return "";
        }
        $link = $this->urlGenerator->generate('content_slug', ['slug' => $content->getAlias()]);
        $title = $content->getTitle();
        return "<a href=\"{$link}\">{$title}</a>";
    }
}
