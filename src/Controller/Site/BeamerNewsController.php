<?php

namespace App\Controller\Site;

use App\Entity\BeamerNews;
use App\Service\BeamerNewsService;
use DateTime;
use Eko\FeedBundle\Feed\FeedManager;
use Eko\FeedBundle\Field\Item\MediaItemField;
use Eko\FeedBundle\Item\Writer\ItemInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Vich\UploaderBundle\Storage\StorageInterface;

#[Route(path: '/beamer', name: 'beamer')]
class BeamerNewsController extends AbstractController
{
    private readonly BeamerNewsService $beamerNewsService;
    private readonly FeedManager $feedManager;
    private readonly StorageInterface $storage;

    public function __construct(BeamerNewsService $beamerNewsService, FeedManager $feedManager, StorageInterface $storage)
    {
        $this->beamerNewsService = $beamerNewsService;
        $this->feedManager = $feedManager;
        $this->storage = $storage;
    }

    #[Route(path: '/feed.rss', name: '_feed')]
    public function feed(Request $request): Response
    {
        $news = $this->beamerNewsService->getActive();

        $baseUrl = $request->getSchemeAndHttpHost();
        $feedItems = array_map(fn(BeamerNews $n) => $this->toFeedElement($n, $baseUrl), $news);

        $feed = $this->feedManager->get('beamer_news');
        $feed->addItemField(new MediaItemField('getFeedItemMedia'));
        $feed->addFromArray($feedItems);

        return new Response($feed->render('rss'), 200, ['Content-Type' => 'application/rss+xml']);
    }

    private function toFeedElement(BeamerNews $beamerNews, string $baseUrl): ItemInterface
    {
        $imageUrl = null;
        $imageMimeType = null;
        $imageSize = 0;

        if ($beamerNews->getImage() && $beamerNews->getImage()->getName()) {
            $path = $this->storage->resolveUri($beamerNews, 'imageFile');
            if ($path) {
                $imageUrl = $baseUrl . $path;
                $imageMimeType = $beamerNews->getImage()->getMimeType() ?? 'image/jpeg';
                $imageSize = $beamerNews->getImage()->getSize() ?? 0;
            }
        }

        return new class ($beamerNews, $imageUrl, $imageMimeType, $imageSize) implements ItemInterface {
            public function __construct(
                private readonly BeamerNews $news,
                private readonly ?string $imageUrl,
                private readonly ?string $imageMimeType,
                private readonly int $imageSize,
            ) {}

            public function getFeedItemTitle(): string
            {
                return $this->news->getTitle() ?? '';
            }

            public function getFeedItemDescription(): string
            {
                return $this->news->getContent() ?? '';
            }

            public function getFeedItemPubDate(): DateTime
            {
                return DateTime::createFromInterface($this->news->activeSince());
            }

            public function getFeedItemLink(): string
            {
                return '';
            }

            public function getFeedItemMedia(): ?array
            {
                if (!$this->imageUrl) {
                    return null;
                }

                return [
                    'type' => $this->imageMimeType,
                    'length' => $this->imageSize,
                    'value' => $this->imageUrl,
                ];
            }
        };
    }
}
