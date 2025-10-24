<?php

namespace App\Service;

use App\Entity\GalleryEvent;
use App\Entity\GalleryImage;
use App\Repository\GalleryEventRepository;
use App\Repository\GalleryImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;

class GalleryService
{
    private readonly GalleryEventRepository $eventRepository;
    private readonly GalleryImageRepository $imageRepository;
    private readonly EntityManagerInterface $em;
    private readonly LoggerInterface $logger;

    public function __construct(
        GalleryEventRepository $eventRepository,
        GalleryImageRepository $imageRepository,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        $this->eventRepository = $eventRepository;
        $this->imageRepository = $imageRepository;
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * @return GalleryEvent[] All events ordered by priority
     */
    public function getAll(): array
    {
        return $this->eventRepository->findAllOrderedByPriority();
    }

    public function count(): int
    {
        return $this->eventRepository->count([]);
    }

    public function hasEvents(): bool
    {
        return $this->eventRepository->count([]) > 0;
    }

    public function delete(GalleryEvent $event): void
    {
        $this->logger->info("Deleted GalleryEvent {$event->getId()} ({$event->getName()})");
        $this->em->remove($event);
        $this->em->flush();
    }

    public function save(GalleryEvent $event): void
    {
        $this->logger->info("Create or Update GalleryEvent {$event->getId()} ({$event->getName()})");
        $this->em->persist($event);
        $this->em->flush();
    }

    public function findOrCreateByName(string $eventName): GalleryEvent
    {
        $event = $this->eventRepository->findOneBy(['name' => $eventName]);
        if (!$event) {
            $event = new GalleryEvent();
            $event->setName($eventName);
            $event->setPriority(999); // Set high priority for new events
            $this->em->persist($event);
            $this->em->flush();
        }
        return $event;
    }

    public function getEventById(int $id): ?GalleryEvent
    {
        return $this->eventRepository->find($id);
    }

    public function renderEvents(): array
    {
        return self::render($this->getAll());
    }

    public function parseEvents(?array $input): bool
    {
        if (!self::check($input)) {
            return false;
        }

        $this->em->beginTransaction();
        $ids = [];
        $events = $this->eventRepository->findAll();
        $events = array_combine(array_map(fn ($e) => $e->getId(), $events), $events);

        // add new events and update existing
        foreach ($input as $index => $a) {
            if (isset($a[self::ARRAY_ID]) && isset($events[$a[self::ARRAY_ID]])) {
                $ids[$a[self::ARRAY_ID]] = true;
                $event = $events[$a[self::ARRAY_ID]];
            } else {
                $event = new GalleryEvent();
            }
            $this->em->persist(
                $event
                    ->setName($a[self::ARRAY_NAME])
                    ->setPriority($index)
            );
        }
        // remove non-existing events (only if they have no images)
        foreach ($events as $event) {
            $id = $event->getId();
            if (!array_key_exists($id, $ids) && $event->getGalleryImages()->count() === 0) {
                $this->em->remove($event);
            }
        }
        try {
            $this->em->flush();
            $this->em->commit();
        } catch (Exception) {
            $this->em->rollback();

            return false;
        }

        return true;
    }

    // Gallery Image Management Methods
    public function saveImage(GalleryImage $image): void
    {
        $this->logger->info("Create or Update GalleryImage {$image->getId()} ({$image->getTitle()})");
        $this->em->persist($image);
        $this->em->flush();
    }

    public function deleteImage(GalleryImage $image): void
    {
        $this->logger->info("Deleted GalleryImage {$image->getId()} ({$image->getTitle()})");
        $this->em->remove($image);
        $this->em->flush();
    }

    public function getAllImagesGroupedByEvent(): array
    {
        return $this->imageRepository->findAllGroupedByEvent();
    }

    public function getEventNames(): array
    {
        return $this->imageRepository->findEvents();
    }

    /**
     * Get all events with their images grouped by event entity
     * @return array Array with event entities as keys and their images as values
     */
    public function getAllEventsWithImages(): array
    {
        // Get all events ordered by priority
        $events = $this->eventRepository->findBy([], ['priority' => 'ASC']);
        $result = [];
        
        foreach ($events as $event) {
            $images = $event->getGalleryImages()->toArray();
            if (!empty($images)) {
                // Sort images by createdAt DESC
                usort($images, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
                $result[$event->getName()] = [
                    'event' => $event,
                    'images' => $images
                ];
            }
        }
        
        // Add images without event
        $imagesWithoutEvent = $this->imageRepository->findByEvent('Ohne Event');
        if (!empty($imagesWithoutEvent)) {
            $result['Ohne Event'] = [
                'event' => null,
                'images' => $imagesWithoutEvent
            ];
        }
        
        return $result;
    }

    private const ARRAY_ID = 'id';
    private const ARRAY_NAME = 'name';
    private const ARRAY_COUNT = 'count';

    // mandatory items for submission
    private const ARRAY_ITEMS = [
        self::ARRAY_NAME,
    ];

    /**
     * @param GalleryEvent[] $events
     */
    private static function render(array $events): array
    {
        $result = [];
        foreach ($events as $event) {
            $result[] = [
                self::ARRAY_ID => $event->getId(),
                self::ARRAY_NAME => $event->getName(),
                self::ARRAY_COUNT => $event->getGalleryImages()->count(),
            ];
        }

        return $result;
    }

    private static function check(array $array): bool
    {
        foreach ($array as $item) {
            foreach (self::ARRAY_ITEMS as $key) {
                if (!array_key_exists($key, $item)) {
                    return false;
                }
            }
            if (isset($item[self::ARRAY_ID]) && !is_int($item[self::ARRAY_ID])) {
                return false;
            }
        }

        return true;
    }
}