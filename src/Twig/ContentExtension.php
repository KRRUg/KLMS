<?php

namespace App\Twig;

use App\Repository\ContentRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class ContentExtension extends AbstractExtension
{
    private readonly ContentRepository $repo;
    private readonly UrlGeneratorInterface $urlGenerator;

    public function __construct(ContentRepository $repo, UrlGeneratorInterface $urlGenerator)
    {
        $this->repo = $repo;
        $this->urlGenerator = $urlGenerator;
    }

    public function getTests(): array
    {
        return [
            new TwigTest('slug', $this->slugExists(...)),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('slug_url', $this->slugUrl(...)),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('slug_link', $this->slugLink(...), ['is_safe' => ['html']]),
        ];
    }

    public function slugExists(string $slug): bool
    {
        $content = $this->repo->findBySlug($slug);

        return !empty($content);
    }

    public function slugLink(string $slug, ?string $title = null, bool $newTab = false): string
    {
        $content = $this->repo->findBySlug($slug);
        if (empty($content)) {
            return $title ?? '';
        }
        $link = $this->urlGenerator->generate('content_slug', ['slug' => $content->getAlias()]);
        $title ??= $content->getTitle();
        $target = $newTab ? 'target="_blank"' : '';

        return "<a href=\"{$link}\" {$target}>{$title}</a>";
    }

    public function slugUrl(string $slug): string
    {
        $content = $this->repo->findBySlug($slug);
        if (empty($content)) {
            return '#';
        }

        return $this->urlGenerator->generate('content_slug', ['slug' => $content->getAlias()]);
    }
}
