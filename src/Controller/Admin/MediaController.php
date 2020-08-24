<?php


namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\Image;
use App\Form\ImageType;
use App\Service\ImageService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Router;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * @Route("/image", name="images")
 */
class MediaController extends BaseController
{
    private $imageService;

    /**
     * ImageController constructor.
     */
    public function __construct(ImageService $imageService, UploaderHelper $uploaderHelper)
    {
        $this->imageService = $imageService;
        $this->uploaderHelper = $uploaderHelper;
    }

    /**
     * @Route("", name="")
     * @Route(".{_format}", name="", defaults={"_format"="html"})
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

        if ($request->getRequestFormat() === 'json') {
            $result = array_map(function (Image $image) {
                return [
                    // title and value required by tinyMCE image list
                    'title' => $image->getImage()->getOriginalName(),
                    'value' => $this->generateUrl('media', ['id' => $image->getId()]),

                    // additional information
                    'dimensions' => $image->getImage()->getDimensions(),
                    'mimeType' => $image->getImage()->getMimeType(),
                    'size' => $image->getImage()->getSize(),
                    'created' => $image->getCreated(),
                    'updated' => $image->getLastModified(),
                    'author' => '', // TODO add author (when user caching is implemented)
                ];
            }, $images);
            return $this->apiResponse($result);
        } else {
            return $this->render("admin/image/index.html.twig", [
                'images' => $images,
                'form_upload' => $form_upload->createView(),
            ]);
        }
    }
}