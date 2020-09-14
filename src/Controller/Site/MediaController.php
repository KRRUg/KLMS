<?php


namespace App\Controller\Site;

use App\Entity\Media;
use App\Repository\MediaRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Vich\UploaderBundle\Handler\DownloadHandler;

/**
 * @Route("/media", name="media")
 */
class MediaController extends AbstractController
{
    private $imageRepository;
    private $downloadHandler;

    /**
     * MediaController constructor.
     * @param $downloadHelper
     */
    public function __construct(MediaRepository $imageRepository, DownloadHandler $downloadHandler)
    {
        $this->imageRepository = $imageRepository;
        $this->downloadHandler = $downloadHandler;
    }

    /**
     * @Route("/{name}", name="")
     */
    public function getMedia(Request $request)
    {
        $name = $request->get('name');
        $media = $this->imageRepository->findByDisplayName($name);
        if (empty($media))
            throw $this->createNotFoundException();
        return $this->downloadHandler->downloadObject($media, $fileField = 'mediaFile', $objectClass = null, $fileName = true, $forceDownload = false);
    }
}