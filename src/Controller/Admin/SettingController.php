<?php

namespace App\Controller\Admin;

use App\Service\SettingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/setting", name="setting")
 * @IsGranted("ROLE_ADMIN_CONTENT")
 */
class SettingController extends AbstractController
{
    private SettingService $service;

    public function __construct(SettingService $service)
    {
        $this->service = $service;
    }

    /**
     * @Route("/", name="", methods={"GET"})
     */
    public function index()
    {
        $k = [];
        $k[''] = [];
        foreach (SettingService::getKeys() as $key) {
            $array = explode('.', $key, 2);
            if (sizeof($array) == 1) {
                $k[''][] = $array[0];
            } else {
                $k[$array[0]][] = $key;
            }
        }

        return $this->render('admin/settings/index.html.twig', [
            'keys' => $k,
            'service' => $this->service,
        ]);
    }

    /**
     * @Route("/edit", name="_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request)
    {
        $key = $request->get('key');
        if (!$this->service->validKey($key)) {
            return $this->redirectToRoute('admin_setting');
        }

        $text = $this->service->get($key);
        $fb = $this->createFormBuilder(['key' => $key, 'text' => $text])
            ->add('key', HiddenType::class);

        switch (SettingService::getType($key)) {
            default:
            case SettingService::TB_TYPE_STRING:
                $fb->add('text', TextType::class, ['required' => false, 'label' => false]);
                break;
            case SettingService::TB_TYPE_HTML:
                $fb->add('text', TextareaType::class, ['required' => false, 'label' => false, 'attr' => ['class' => 'wysiwyg']]);
                break;
            case SettingService::TB_TYPE_URL:
                $fb->add('text', UrlType::class, ['required' => false, 'label' => false]);
                break;
        }

        $form = $fb->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if ($data['key'] === $key) {
                $text = $data['text'];
                $text = empty($text) ? "" : $text;
                $this->service->set($key, $text);
            }
            return $this->redirectToRoute("admin_setting");
        }

        return $this->render('admin/settings/edit.html.twig', [
            'key' => $key,
            'desc' => SettingService::getDescription($key),
            'is_html' => SettingService::isHTML($key),
            'form' => $form->createView(),
        ]);
    }
}
