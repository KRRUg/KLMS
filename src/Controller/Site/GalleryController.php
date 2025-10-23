<?php

namespace App\Controller\Site;

use App\Entity\GalleryImage;
use App\Repository\GalleryImageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gallery', name: 'gallery')]
class GalleryController extends AbstractController
{
    #[Route('', name: '', methods: ['GET'])]
    public function index(GalleryImageRepository $repository): Response
    {
        $photosByEvent = $repository->findAllGroupedByEvent();
        $events = $repository->findEvents();

        return $this->render('site/gallery/index.html.twig', [
            'photosByEvent' => $photosByEvent,
            'events' => $events,
        ]);
    }

    #[Route('/event/{event}', name: '_event', methods: ['GET'])]
    public function event(string $event, GalleryImageRepository $repository): Response
    {
        $photos = $repository->findByEvent($event);
        
        if (empty($photos)) {
            throw $this->createNotFoundException('Event not found');
        }

        return $this->render('site/gallery/event.html.twig', [
            'event' => $event,
            'photos' => $photos,
        ]);
    }
}