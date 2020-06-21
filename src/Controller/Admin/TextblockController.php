<?php

namespace App\Controller\Admin;

use App\Service\TextBlockService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/textblock", name="textblock")
 * @IsGranted("ROLE_ADMIN_CONTENT")
 */
class TextblockController extends AbstractController
{
    private $service;

    public function __construct(TextBlockService $service)
    {
        $this->service = $service;
    }

    /**
     * @Route("/", name="", methods={"GET"})
     */
    public function index()
    {
        return $this->render('admin/textblock/index.html.twig', [
            'keys' => TextBlockService::TEXT_BLOCK_KEYS,
            'modification' => $this->service->getModificationDates()
        ]);
    }

    /**
     * @Route("/edit", name="_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request)
    {
        $key = $request->get('key');
        if (empty($key) || !$this->service->validKey($key)) {
            return $this->redirectToRoute('admin_textblock');
        }
        $text = $this->service->get($key);
        $form = $this->createFormBuilder(['key' => $key, 'text' => $text])
            ->add('key', HiddenType::class)
            ->add('text', TextareaType::class, ['required' => false, 'label' => false])
            ->add('save', SubmitType::class, ['label' => 'Speichern'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if ($data['key'] !== $key) {
                // TODO handle me
            }
            $text = $data['text'];
            $text = empty($text) ? "" : $text;
            $this->service->set($key, $text);
            return $this->redirectToRoute("admin_textblock");
        }

        return $this->render('admin/textblock/edit.html.twig', [
            'key' => $key,
            'desc' => TextBlockService::TEXT_BLOCK_KEYS[$key],
            'form' => $form->createView()
        ]);
    }
}
