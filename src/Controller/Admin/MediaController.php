<?php


namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\Image;
use App\Form\ImageType;
use App\Service\ImageService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * @Route("/media", name="media")
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
    }

    private function image2json(Image $image)
    {
        return [
            // title and value required by tinyMCE image list
            'id' => $image->getId(),
            'title' => $image->getName(),
            'value' => $this->generateUrl('media', ['name' => $image->getName()]),

            // additional information
            'dimensions' => $image->getImage()->getDimensions(),
            'mimeType' => $image->getImage()->getMimeType(),
            'size' => $image->getImage()->getSize(),
            'created' => $image->getCreated(),
            'updated' => $image->getLastModified(),
            'author' => '', // TODO add author (when user caching is implemented)
        ];
    }

    /**
     * @Route(".{_format}", name="", defaults={"_format"="html"})
     */
    public function index(Request $request)
    {
        $images = $this->imageService->getAll();
        $form_upload = $this->createForm(ImageType::class);

        $form_upload->handleRequest($request);
        if ($form_upload->isSubmitted() && $form_upload->isValid()) {
            $this->imageService->save($form_upload->getData());
            return $this->redirectToRoute('admin_media');
        }

        if ($request->getRequestFormat() === 'json') {
            $result = array_map(function (Image $image) {
                return $this->image2json($image);
            }, $images);
            return $this->apiResponse($result);
        } else {
            return $this->render("admin/image/index.html.twig", [
                'images' => $images,
                'form_upload' => $form_upload->createView(),
            ]);
        }
    }

    /**
     * @Route("/delete/{id}", name="_delete")
     * @ParamConverter()
     */
    public function delete(Image $image)
    {
        $this->imageService->delete($image);
        return $this->redirectToRoute('admin_media');
    }

    /**
     * @Route("/detail/{id}.{_format}", name="_detail", defaults={"_format"="html"})
     * @ParamConverter()
     */
    public function detail(Request $request, Image $image)
    {
        if ($request->getRequestFormat() === 'json') {
            return $this->apiResponse($this->image2json($image));
        } else {
            // TODO implement?
        }
    }
}