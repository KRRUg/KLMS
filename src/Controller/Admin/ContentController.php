<?php

namespace App\Controller\Admin;

use App\Entity\Content;
use App\Form\ContentType;
use App\Service\ContentService;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
     * @Route("/edit/{id}", name="_edit", methods={"GET","POST"})
     * @ParamConverter()
     */
    public function edit(Request $request, Content $content)
    {
        $form = $this->createForm(ContentType::class, $content);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $news = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($news);
            $em->flush();
            return $this->redirectToRoute("admin_content");
        }

        return $this->render("admin/content/new.html.twig", [
            'method' => 'Update',
            'form' => $form->createView()
        ]);
    }
}
