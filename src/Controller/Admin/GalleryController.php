<?php

namespace App\Controller\Admin;

use App\Entity\GalleryImage;
use App\Repository\GalleryImageRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\File;
use Psr\Log\LoggerInterface;

use Doctrine\ORM\EntityManagerInterface;
#[IsGranted('ROLE_ADMIN_MEDIA')]
#[Route('gallery', name: 'gallery')]
class GalleryController extends AbstractController
{
    #[Route('', name: '', methods: ['GET'])]
    public function index(GalleryImageRepository $repository): Response
    {
        $photosByEvent = $repository->findAllGroupedByEvent();
        $events = $repository->findEvents();

        return $this->render('admin/gallery/index.html.twig', [
            'photosByEvent' => $photosByEvent,
            'events' => $events,
        ]);
    }

    #[Route('/upload', name: '_upload', methods: ['GET', 'POST'])]
    public function upload(Request $request, GalleryImageRepository $repository): Response
    {
        $galleryImage = new GalleryImage();

        $form = $this->createFormBuilder($galleryImage)
            ->add('event', TextType::class, [
                'label' => 'Event Name',
                'attr' => ['placeholder' => 'z.B. lan-party-2023']
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
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Bitte wählen Sie eine gültige Bilddatei (JPEG, PNG, GIF, WebP)'
                    ])
                ]
            ])
            ->add('submit', SubmitType::class, ['label' => 'Bild hochladen'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Create event directory if it doesn't exist
            $eventFolder = $this->getParameter('kernel.project_dir') . '/public/images/gallery/' . $galleryImage->getEvent();
            if (!is_dir($eventFolder)) {
                mkdir($eventFolder, 0755, true);
            }

            $repository->save($galleryImage, true);

            $this->addFlash('success', 'Bild wurde erfolgreich hochgeladen!');
            return $this->redirectToRoute('admin_gallery');
        }

        return $this->render('admin/gallery/upload.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/bulk-upload', name: '_bulk_upload', methods: ['GET', 'POST'])]
    public function bulkUpload(Request $request, GalleryImageRepository $repository, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $event = $request->request->get('event');
            $files = $request->files->get('images');
            
            if (!$event || trim($event) === '') {
                return $this->json(['error' => 'Event name is required'], 400);
            }
            
            if (!$files || !is_array($files) || count($files) === 0) {
                return $this->json(['error' => 'No files uploaded'], 400);
            }

            $uploaded = 0;
            $errors = [];
            
            foreach ($files as $file) {
                try {
                    // Validate file
                    $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array($file->getMimeType(), $allowedMimes)) {
                        $errors[] = $file->getClientOriginalName() . ': Invalid file type';
                        continue;
                    }

                    if ($file->getSize() > 10 * 1024 * 1024) { // 10MB
                        $errors[] = $file->getClientOriginalName() . ': File too large';
                        continue;
                    }

                    // Create GalleryImage entity
                    $galleryImage = new GalleryImage();
                    $galleryImage->setEvent(trim($event));
                    $galleryImage->setTitle(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                    $galleryImage->setImageFile($file);

                    $repository->save($galleryImage, true);
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
        $events = $repository->findEvents();
        return $this->render('admin/gallery/bulk-upload.html.twig', [
            'events' => $events
        ]);
    }

    #[Route('/edit/{uuid}', name: '_edit', methods: ['GET', 'POST'])]
    public function edit(GalleryImage $galleryImage, Request $request, GalleryImageRepository $repository): Response
    {
        $form = $this->createFormBuilder($galleryImage)
            ->add('event', TextType::class, [
                'label' => 'Event Name',
                'attr' => ['placeholder' => 'z.B. lan-party-2023']
            ])
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
            $repository->save($galleryImage, true);

            $this->addFlash('success', 'Bild wurde erfolgreich aktualisiert!');
            return $this->redirectToRoute('admin_gallery');
        }

        return $this->render('admin/gallery/edit.html.twig', [
            'form' => $form->createView(),
            'galleryImage' => $galleryImage,
        ]);
    }

    #[Route('/delete/{uuid}', name: '_delete', methods: ['POST'])]
    public function delete(GalleryImage $galleryImage, Request $request, GalleryImageRepository $repository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$galleryImage->getId(), $request->request->get('_token'))) {
            $repository->remove($galleryImage, true);
            $this->addFlash('success', 'Bild wurde erfolgreich gelöscht!');
        }

        return $this->redirectToRoute('admin_gallery');
    }
}