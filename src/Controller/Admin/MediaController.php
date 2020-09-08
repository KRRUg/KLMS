<?php


namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\Media;
use App\Form\MediaType;
use App\Service\MediaService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/media", name="media")
 */
class MediaController extends BaseController
{
    private $mediaService;

    /**
     * ImageController constructor.
     */
    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    private function image2json(Media $image)
    {
        return [
            // title and value required by tinyMCE image list
            'id' => $image->getId(),
            'title' => $image->getMedia()->getOriginalName(),
            'value' => $this->generateUrl('media', ['name' => $image->getMedia()->getName()]),

            // additional information
//            'dimensions' => $image->getMedia()->getDimensions(),
//            'mimeType' => $image->getMedia()->getMimeType(),
//            'size' => $image->getMedia()->getSize(),
//            'created' => $image->getCreated(),
//            'updated' => $image->getLastModified(),
//            'author' => '', // TODO add author (when user caching is implemented)
        ];
    }

    private function mediaByFilter(string $filter)
    {
        switch ($filter) {
            case "image":
                return $this->mediaService->getImages();
            case "document":
            case "doc":
                return $this->mediaService->getDocuments();
            default:
                return $this->mediaService->getAll();
        }
    }

    /**
     * @Route(".{_format}", name="", defaults={"_format"="html"})
     */
    public function index(Request $request)
    {
        $filter = $request->get('filter', '');
        $media = $this->mediaByFilter($filter);

        $form_upload = $this->createForm(MediaType::class);

        $form_upload->handleRequest($request);
        if ($form_upload->isSubmitted()) {
            if ($form_upload->isValid()) {
                $this->mediaService->save($form_upload->getData());
            } else {
                $this->addFlash('danger', 'Invalid file uploaded.');
            }
            return $this->redirectToRoute('admin_media');
        }

        if ($request->getRequestFormat() === 'json') {
            $result = array_map(function (Media $m) {
                return $this->image2json($m);
            }, $media);
            return $this->apiResponse($result);
        } else {
            return $this->render("admin/image/index.html.twig", [
                'media' => $media,
                'form_upload' => $form_upload->createView(),
            ]);
        }
    }

    /**
     * @Route("/delete/{id}", name="_delete")
     * @ParamConverter()
     */
    public function delete(Media $image)
    {
        $this->mediaService->delete($image);
        return $this->redirectToRoute('admin_media');
    }

    /**
     * @Route("/detail/{id}.{_format}", name="_detail", defaults={"_format"="html"})
     * @ParamConverter()
     */
    public function detail(Request $request, Media $image)
    {
        if ($request->getRequestFormat() === 'json') {
            return $this->apiResponse($this->image2json($image));
        } else {
            // TODO implement?
        }
    }
}