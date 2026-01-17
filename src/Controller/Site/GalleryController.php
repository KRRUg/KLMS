<?php

namespace App\Controller\Site;

use App\Service\GalleryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/gallery', name: 'gallery')]
class GalleryController extends AbstractController
{
    #[Route('', name: '', methods: ['GET'])]
    public function index(GalleryService $galleryService): Response
    {
        $eventsWithImages = $galleryService->getAllEventsWithImages();

        return $this->render('site/gallery/index.html.twig', [
            'eventsWithImages' => $eventsWithImages,
        ]);
    }

    #[Route('/event/{id}', name: '_event', methods: ['GET'])]
    public function event(int $id, GalleryService $galleryService): Response
    {
        // Get the specific event by ID
        $event = $galleryService->getEventById($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        // Get images for this event
        $images = $event->getGalleryImages()->toArray();
        
        // Sort images by createdAt DESC
        usort($images, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());

        return $this->render('site/gallery/event.html.twig', [
            'event' => $event,
            'eventName' => $event->getName(),
            'photos' => $images,
        ]);
    }
}
