<?php

namespace App\Controller\Admin;

use App\Entity\GalleryImage;
use App\Entity\GalleryEvent;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints as Assert;
use App\Service\GalleryService;

#[IsGranted('ROLE_ADMIN_MEDIA')]
#[Route('gallery', name: 'gallery')]
class GalleryController extends AbstractController
{
    #[Route('', name: '', methods: ['GET'])]
    public function index(GalleryService $galleryService): Response
    {
        $eventsWithImages = $galleryService->getAllEventsWithImages();

        return $this->render('admin/gallery/index.html.twig', [
            'eventsWithImages' => $eventsWithImages,
        ]);
    }

    #[Route('/upload', name: '_upload', methods: ['GET', 'POST'])]
    public function upload(Request $request, GalleryService $galleryService): Response
    {
        $galleryImage = new GalleryImage();

        $form = $this->createFormBuilder($galleryImage)
            ->add('galleryEvent', EntityType::class, [
                'class' => GalleryEvent::class,
                'choice_label' => 'name',
                'label' => 'Event auswählen',
                'placeholder' => '-- Event auswählen --',
                'choices' => $galleryService->getAll()
            ])
            ->add('title', TextType::class, [
                'label' => 'Titel (optional)',
                'required' => false
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Beschreibung (optional)',
                'required' => false,
                'attr' => ['rows' => 3]
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Bild auswählen',
                'constraints' => [
                    new File([
                        'maxSize' => '20M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Bitte wählen Sie eine gültige Bilddatei (JPEG, PNG, WebP). SVG und andere Formate sind nicht erlaubt.'
                    ])
                ]
            ])
            ->add('submit', SubmitType::class, ['label' => 'Bild hochladen'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $galleryService->saveImage($galleryImage);

            $this->addFlash('success', 'Bild wurde erfolgreich hochgeladen!');
            return $this->redirectToRoute('admin_gallery');
        }

        return $this->render('admin/gallery/upload.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/bulk-upload', name: '_bulk_upload', methods: ['GET', 'POST'])]
    public function bulkUpload(Request $request, GalleryService $galleryService): Response
    {
        if ($request->isMethod('POST')) {
            $eventName = $request->request->get('event');
            $files = $request->files->get('images');
            
            if (!$eventName || trim($eventName) === '') {
                return $this->json(['error' => 'Event name is required'], 400);
            }
            
            // Find or create GalleryEvent
            $galleryEvent = $galleryService->findOrCreateByName($eventName);
            
            if (!$files || !is_array($files) || count($files) === 0) {
                return $this->json(['error' => 'No files uploaded'], 400);
            }

            $uploaded = 0;
            $errors = [];
            
            foreach ($files as $file) {
                try {
                    // Validate file
                    $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                    if (!in_array($file->getMimeType(), $allowedMimes)) {
                        $errors[] = $file->getClientOriginalName() . ': Ungültiger Dateityp. Nur JPEG, PNG und WebP sind erlaubt.';
                        continue;
                    }

                    if ($file->getSize() > 20 * 1024 * 1024) { // 20MB
                        $errors[] = $file->getClientOriginalName() . ': Datei ist zu groß (max. 20MB)';
                        continue;
                    }

                    // Create GalleryImage entity
                    $galleryImage = new GalleryImage();
                    $galleryImage->setGalleryEvent($galleryEvent);
                    $galleryImage->setTitle(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                    $galleryImage->setImageFile($file);

                    $galleryService->saveImage($galleryImage);
                    $uploaded++;

                } catch (\Exception $e) {
                    $errors[] = $file->getClientOriginalName() . ': ' . $e->getMessage();
                }
            }

            return $this->json([
                'success' => true,
                'uploaded' => $uploaded,
                'errors' => $errors
            ]);
        }

        // GET request - show bulk upload form
        $events = $galleryService->getAll();
        return $this->render('admin/gallery/bulk-upload.html.twig', [
            'events' => $events
        ]);
    }

    #[Route('/edit/{uuid}', name: '_edit', methods: ['GET', 'POST'])]
    public function edit(GalleryImage $galleryImage, Request $request, GalleryService $galleryService): Response
    {
        $form = $this->createFormBuilder($galleryImage)
            ->add('title', TextType::class, [
                'label' => 'Titel',
                'required' => false
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Beschreibung',
                'required' => false,
                'attr' => ['rows' => 4]
            ])
            ->add('submit', SubmitType::class, ['label' => 'Änderungen speichern'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $galleryService->saveImage($galleryImage);

            $this->addFlash('success', 'Bild wurde erfolgreich aktualisiert!');
            return $this->redirectToRoute('admin_gallery');
        }

        return $this->render('admin/gallery/edit.html.twig', [
            'form' => $form->createView(),
            'galleryImage' => $galleryImage,
        ]);
    }

    #[Route('/delete/{uuid}', name: '_delete', methods: ['POST'])]
    public function delete(GalleryImage $galleryImage, Request $request, GalleryService $galleryService): Response
    {
        if ($this->isCsrfTokenValid('delete'.$galleryImage->getId(), $request->request->get('_token'))) {
            $galleryService->deleteImage($galleryImage);
            $this->addFlash('success', 'Bild wurde erfolgreich gelöscht!');
        }

        return $this->redirectToRoute('admin_gallery');
    }

    #[Route('/events', name: '_events', methods: ['GET', 'POST'])]
    public function events(Request $request, GalleryService $galleryService): Response
    {
        $array = $galleryService->renderEvents();
        $form = $this->createFormBuilder()
            ->add('events', HiddenType::class, [
                'required' => true,
                'data' => json_encode($array, JSON_THROW_ON_ERROR),
                'constraints' => [new Assert\Json()],
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $array = json_decode((string) $form->getData()['events'], true, 512, JSON_THROW_ON_ERROR);
            $success = $galleryService->parseEvents($array);
            if ($success) {
                $this->addFlash('success', 'Events wurden erfolgreich gespeichert!');
            } else {
                $this->addFlash('danger', 'Events Speichern fehlgeschlagen');
            }

            return $this->redirectToRoute('admin_gallery_events');
        }

        return $this->render('admin/gallery/events.html.twig', [
            'form' => $form->createView(),
        ]);
    }

}
