<?php

namespace App\EventListener;

use App\Repository\ContentRepository;
use App\Repository\NewsRepository;
use App\Repository\TourneyRepository;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Event Listener für die automatische Sitemap-Generierung
 * 
 * Dieser Listener fügt automatisch alle öffentlichen Seiten zur XML-Sitemap hinzu:
 * - Statische Seiten (Homepage, Content-Seiten)
 * - News-Artikel
 * - Turniere
 */
class SitemapSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private ContentRepository $contentRepository,
        private NewsRepository $newsRepository,
        private TourneyRepository $tourneyRepository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SitemapPopulateEvent::class => 'populate',
        ];
    }

    public function populate(SitemapPopulateEvent $event): void
    {
        $this->registerStaticPages($event->getUrlContainer());
        $this->registerContentPages($event->getUrlContainer());
        $this->registerNewsPages($event->getUrlContainer());
        $this->registerTourneyPages($event->getUrlContainer());
    }

    /**
     * Registriert statische Seiten wie Homepage, News-Übersicht, etc.
     */
    private function registerStaticPages(UrlContainerInterface $urls): void
    {
        // Homepage
        $urls->addUrl(
            new UrlConcrete(
                $this->urlGenerator->generate('index', [], UrlGeneratorInterface::ABSOLUTE_URL),
                new \DateTime(),
                UrlConcrete::CHANGEFREQ_DAILY,
                1.0 // Höchste Priorität für Homepage
            ),
            'default'
        );

        // News-Übersicht
        $urls->addUrl(
            new UrlConcrete(
                $this->urlGenerator->generate('news', [], UrlGeneratorInterface::ABSOLUTE_URL),
                new \DateTime(),
                UrlConcrete::CHANGEFREQ_DAILY,
                0.9
            ),
            'default'
        );

        // Turnier-Übersicht
        $urls->addUrl(
            new UrlConcrete(
                $this->urlGenerator->generate('tourney', [], UrlGeneratorInterface::ABSOLUTE_URL),
                new \DateTime(),
                UrlConcrete::CHANGEFREQ_WEEKLY,
                0.9
            ),
            'default'
        );

        // Content-Übersicht
        $urls->addUrl(
            new UrlConcrete(
                $this->urlGenerator->generate('content_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
                new \DateTime(),
                UrlConcrete::CHANGEFREQ_MONTHLY,
                0.6
            ),
            'default'
        );
    }

    /**
     * Registriert alle Content-Seiten
     */
    private function registerContentPages(UrlContainerInterface $urls): void
    {
        $contents = $this->contentRepository->findAll();

        foreach ($contents as $content) {
            // Verwende Slug wenn vorhanden, sonst ID
            $routeName = !empty($content->getAlias()) ? 'content_slug' : 'content';
            $routeParams = !empty($content->getAlias()) 
                ? ['slug' => $content->getAlias()] 
                : ['id' => $content->getId()];

            $urls->addUrl(
                new UrlConcrete(
                    $this->urlGenerator->generate($routeName, $routeParams, UrlGeneratorInterface::ABSOLUTE_URL),
                    $content->getLastModified() ?? $content->getCreated() ?? new \DateTime(),
                    UrlConcrete::CHANGEFREQ_MONTHLY,
                    0.5
                ),
                'content'
            );
        }
    }

    /**
     * Registriert alle News-Artikel
     */
    private function registerNewsPages(UrlContainerInterface $urls): void
    {
        // Alle News holen und nur aktive verwenden
        $allNews = $this->newsRepository->findBy(
            [],
            ['created' => 'DESC']
        );

        foreach ($allNews as $article) {
            // Nur aktive/veröffentlichte News zur Sitemap hinzufügen
            if (!$article->isActive()) {
                continue;
            }

            $urls->addUrl(
                new UrlConcrete(
                    $this->urlGenerator->generate(
                        'news_detail', 
                        ['id' => $article->getId()], 
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                    $article->getLastModified() ?? $article->getCreated() ?? new \DateTime(),
                    UrlConcrete::CHANGEFREQ_MONTHLY,
                    0.7
                ),
                'news'
            );
        }
    }

    /**
     * Registriert alle Turniere
     */
    private function registerTourneyPages(UrlContainerInterface $urls): void
    {
        $tourneys = $this->tourneyRepository->findAll();

        foreach ($tourneys as $tourney) {
            $urls->addUrl(
                new UrlConcrete(
                    $this->urlGenerator->generate(
                        'tourney_show', 
                        ['id' => $tourney->getId()], 
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                    $tourney->getLastModified() ?? $tourney->getCreated() ?? new \DateTime(),
                    UrlConcrete::CHANGEFREQ_WEEKLY,
                    0.8
                ),
                'tourney'
            );
        }
    }
}
