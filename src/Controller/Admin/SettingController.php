<?php

namespace App\Controller\Admin;

use App\Form\HtmlTextareaType;
use App\Service\SettingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Vich\UploaderBundle\Form\Type\VichFileType;

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
        if (empty($k[''])) {
            unset($k['']);
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

        $fb = $this->createFormBuilder($this->service->getSettingObject($key))
            ->add('key', HiddenType::class);

        switch (SettingService::getType($key)) {
            default:
            case SettingService::TB_TYPE_STRING:
                $fb->add('text', TextType::class, ['required' => false, 'label' => false]);
                break;
            case SettingService::TB_TYPE_HTML:
                $fb->add('text', HtmlTextareaType::class, ['required' => false, 'label' => false]);
                break;
            case SettingService::TB_TYPE_URL:
                $fb->add('text', UrlType::class, ['required' => false, 'label' => false]);
                break;
            case SettingService::TB_TYPE_FILE:
                $fb->add('file', VichFileType::class, [
                    'required' => false,
                    'label' => false,
                    'download_uri' => false,
                    'allow_delete' => true,
                    'delete_label' => "Löschen",
                    ]);
                break;
            case SettingService::TB_TYPE_BOOL:
                $fb->add('text', ChoiceType::class, [
                    'choices' => [
                        'Aktiviert' => '1',
                        'Deaktiviert' => '0'
                    ],
                    'expanded' => true,
                    'required' => true,
                    'label' => false,
                ]);
                break;
        }

        $form = $fb->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->service->setSettingsObject($data);
            return $this->redirectToRoute("admin_setting");
        }

        return $this->render('admin/settings/edit.html.twig', [
            'key' => $key,
            'desc' => SettingService::getDescription($key),
            'form' => $form->createView(),
        ]);
    }
}
