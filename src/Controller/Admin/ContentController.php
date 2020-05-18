<?php

namespace App\Controller\Admin;

use App\Service\ContentService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/content", name="content")
 */
class ContentController extends AbstractController
{
    private $logger;
    private $contentService;

    /**
     * ContentController constructor.
     * @param $logger
     * @param $contentService
     */
    public function __construct(LoggerInterface $logger, ContentService $contentService)
    {
        $this->logger = $logger;
        $this->contentService = $contentService;
    }

    /**
     * @Route("", name="", methods={"GET"})
     */
    public function index()
    {
        $content = $this->contentService->getContent();

        return $this->render('admin/content/index.html.twig', [
            'content' => $content
        ]);
    }

    /**
     * @Route("/edit", name="_edit", methods={"GET","POST"})
     */
    public function edit()
    {

    }

}
