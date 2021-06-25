<?php


namespace App\Controller\Site;

use App\Repository\MediaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Vich\UploaderBundle\Handler\DownloadHandler;

/**
 * @Route("/media", name="media")
 */
class MediaController extends AbstractController
{
    private MediaRepository $mediaRepository;
    private DownloadHandler $downloadHandler;

    /**
     * MediaController constructor.
     * @param $downloadHelper
     */
    public function __construct(MediaRepository $mediaRepository, DownloadHandler $downloadHandler)
    {
        $this->mediaRepository = $mediaRepository;
        $this->downloadHandler = $downloadHandler;
    }

    /**
     * @Route("/{name}", name="")
     */
    public function getMedia(Request $request)
    {
        $name = $request->get('name');
        $media = $this->mediaRepository->findByDisplayName($name);
        if (empty($media))
            throw $this->createNotFoundException();
        return $this->downloadHandler->downloadObject($media, $fileField = 'mediaFile', $objectClass = null, $fileName = true, $forceDownload = false);
    }
}