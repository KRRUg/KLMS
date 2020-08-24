<?php


namespace App\Controller\Site;

use App\Entity\Image;
use App\Repository\ImageRepository;
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
    public function __construct(ImageRepository $imageRepository, DownloadHandler $downloadHandler)
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
        $image = $this->imageRepository->findByNameAndId($name);
        if (empty($image))
            throw $this->createNotFoundException();
        return $this->downloadHandler->downloadObject($image, $fileField = 'imageFile', $objectClass = null, $fileName = null, $forceDownload = false);
    }
}