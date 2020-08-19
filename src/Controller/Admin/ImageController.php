<?php


namespace App\Controller\Admin;

use App\Entity\Image;
use App\Form\ImageType;
use App\Form\NewsType;
use App\Repository\ImageRepository;
use App\Service\ImageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/image", name="images")
 */
class ImageController extends AbstractController
{
    private $imageService;

    /**
     * ImageController constructor.
     */
    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * @Route("", name="")
     */
    public function index(Request $request)
    {
        $images = $this->imageService->getAll();
        $form_upload = $this->createForm(ImageType::class);

        $form_upload->handleRequest($request);
        if ($form_upload->isSubmitted() && $form_upload->isValid()) {
            $this->imageService->save($form_upload->getData());
            return $this->redirectToRoute("admin_images");
        }

        return $this->render("admin/image/index.html.twig", [
            'images' => $images,
            'form_upload' => $form_upload->createView(),
        ]);
    }
}