<?php

namespace App\Controller\Admin;

use App\Entity\Content;
use App\Exception\ServiceException;
use App\Form\ContentType;
use App\Service\ContentService;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/content", name="content")
 * @IsGranted("ROLE_ADMIN_CONTENT")
 */
class ContentController extends AbstractController
{
    private const CSRF_TOKEN_DELETE = "contentDeleteToken";

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
        $content = $this->contentService->getAll();
        ksort($content);

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
            $this->contentService->save($form->getData());
            return $this->redirectToRoute("admin_content");
        }

        return $this->render("admin/content/edit.html.twig", [
            'form' => $form->createView(),
            'csrf_token_delete' => self::CSRF_TOKEN_DELETE,
        ]);
    }

    /**
     * @Route("/new", name="_new", methods={"GET","POST"})
     */
    public function new(Request $request)
    {
        $form = $this->createForm(ContentType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->contentService->save($form->getData());
            return $this->redirectToRoute("admin_content");
        }

        return $this->render("admin/content/edit.html.twig", [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/delete/{id}", name="_delete")
     * @ParamConverter()
     */
    public function delete(Request $request, Content $content) {
        $token = $request->request->get('_token');
        if(!$this->isCsrfTokenValid(self::CSRF_TOKEN_DELETE, $token)) {
            $this->addFlash('error', 'The CSRF token is invalid.');
        } else {
            try {
                $this->contentService->delete($content);
            } catch (ServiceException $e) {
                switch ($e->getCause()) {
                    case ServiceException::CAUSE_IN_USE:
                        $this->addFlash('danger', 'Konnte Content nicht löschen, da in Verwendung.');
                        break;
                    case ServiceException::CAUSE_DONT_EXIST:
                        $this->addFlash('warning', 'Content nicht gefunden.');
                        break;
                }
            }
        }
        return $this->redirectToRoute("admin_content");
    }
}
