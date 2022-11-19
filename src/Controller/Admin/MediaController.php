<?php

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\Media;
use App\Exception\ServiceException;
use App\Form\MediaType;
use App\Service\MediaService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/media", name="media")
 * @IsGranted({"ROLE_ADMIN_CONTENT", "ROLE_ADMIN_NEWS"})
 */
class MediaController extends BaseController
{
    private const CSRF_TOKEN_DELETE = 'mediaDeleteToken';

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
            'title' => $image->getDisplayName(),
            'value' => $this->generateUrl('media', ['name' => $image->getDisplayName()]),
            'mimeType' => $image->getMimeType(),
        ];
    }

    private function mediaByFilter(string $filter)
    {
        return match ($filter) {
            'image' => $this->mediaService->getImages(),
            'document', 'doc' => $this->mediaService->getDocuments(),
            default => $this->mediaService->getAll(),
        };
    }

    /**
     * @Route(".{_format}", name="", defaults={"_format"="html"})
     */
    public function index(Request $request): Response
    {
        $filter = $request->get('filter', '');
        $media = $this->mediaByFilter($filter);

        $form_upload = $this->createForm(MediaType::class);

        $form_upload->handleRequest($request);
        if ($form_upload->isSubmitted()) {
            if ($form_upload->isValid()) {
                $data = $form_upload->getData();
                $overwrite = boolval($form_upload['overwrite']->getData());
                try {
                    $this->mediaService->save($data, $overwrite);
                } catch (ServiceException $e) {
                    $this->flashException($e);
                }
            } else {
                $this->addFlash('danger', 'Invalid file uploaded.');
            }

            return $this->redirectToRoute('admin_media');
        }

        if ($request->getRequestFormat() === 'json') {
            $result = array_map(fn (Media $m) => $this->image2json($m), $media);

            return $this->apiResponse($result);
        } else {
            return $this->render('admin/media/index.html.twig', [
                'media' => $media,
                'csrf_token_delete' => self::CSRF_TOKEN_DELETE,
                'form_upload' => $form_upload->createView(),
            ]);
        }
    }

    /**
     * @Route("/delete/{id}", name="_delete")
     */
    public function delete(Request $request, Media $image): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_DELETE, $token)) {
            throw $this->createAccessDeniedException('The CSRF token is invalid.');
        }
        $this->mediaService->delete($image);
        $this->addFlash('success', 'Medium gelöscht.');

        return $this->redirectToRoute('admin_media');
    }

    /**
     * @Route("/detail/{id}.{_format}", name="_detail", defaults={"_format"="html"})
     */
    public function detail(Request $request, Media $image): Response
    {
        if ($request->getRequestFormat() === 'json') {
            return $this->apiResponse($this->image2json($image));
        } else {
            // TODO implement?
        }
    }
}
