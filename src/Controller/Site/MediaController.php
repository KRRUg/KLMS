<?php


namespace App\Controller\Site;

use App\Entity\Image;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Vich\UploaderBundle\Handler\DownloadHandler;

class MediaController extends AbstractController
{
    private $downloadHandler;

    /**
     * MediaController constructor.
     * @param $downloadHelper
     */
    public function __construct(DownloadHandler $downloadHandler)
    {
        $this->downloadHandler = $downloadHandler;
    }

    /**
     * @Route("/media/{id}", name="media")
     * @ParamConverter()
     */
    public function getMedia(Image $image)
    {
        return $this->downloadHandler->downloadObject($image, $fileField = 'imageFile', $objectClass = null, $fileName = null, $forceDownload = false);
    }
}