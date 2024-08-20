<?php

namespace App\Controller\Admin;

use App\Form\HtmlTextareaType;
use App\Service\SettingService;
use App\Service\SettingType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Vich\UploaderBundle\Form\Type\VichFileType;

#[Route(path: '/setting', name: 'setting')]
#[IsGranted('ROLE_ADMIN_CONTENT')]
class SettingController extends AbstractController
{
    private readonly SettingService $service;

    public function __construct(SettingService $service)
    {
        $this->service = $service;
    }

    #[Route(path: '/', name: '', methods: ['GET'])]
    public function index(): Response
    {
        $k = [];
        $k[''] = [];
        foreach (SettingService::getKeys() as $key) {
            $array = explode('.', (string) $key, 2);
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

    #[Route(path: '/edit', name: '_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request): Response
    {
        $key = $request->get('key', '');
        if (!$this->service->validKey($key)) {
            return $this->redirectToRoute('admin_setting');
        }

        $fb = $this->createFormBuilder($this->service->getSettingObject($key))
            ->setAction($this->generateUrl('admin_setting_edit', ['key' => $key]))
            ->add('key', HiddenType::class);

        $options = ['required' => false, 'label' => false];
        switch (SettingService::getType($key)) {
            default:
            case SettingType::String:
                $fb->add('text', TextType::class, $options);
                break;
            case SettingType::HTML:
                $fb->add('text', HtmlTextareaType::class, $options);
                break;
            case SettingType::URL:
                $fb->add('text', UrlType::class, $options);
                break;
            case SettingType::Integer:
                $fb->add('text', IntegerType::class, $options);
                $fb->get('text')
                    ->addModelTransformer(new CallbackTransformer(
                        fn($a) => intval($a),
                        fn($a) => strval($a)
                    ));
                break;
            case SettingType::Money:
                $fb->add('text', MoneyType::class, array_merge($options, ['divisor' => 100]));
                $fb->get('text')
                    ->addModelTransformer(new CallbackTransformer(
                        fn($a) => intval($a),
                        fn($a) => empty($a) ? "0" : strval($a)
                    ));
                break;
            case SettingType::File:
                $fb->add('file', VichFileType::class, [
                    'required' => false,
                    'label' => false,
                    'download_uri' => false,
                    'allow_delete' => true,
                    'delete_label' => 'Löschen',
                    ]);
                break;
            case SettingType::Bool:
                $fb->add('text', ChoiceType::class, [
                    'choices' => [
                        'Aktiviert' => '1',
                        'Deaktiviert' => '0',
                    ],
                    'expanded' => true,
                    'required' => true,
                    'label' => false,
                ]);
                break;
        }

        $fb->add('delete', SubmitType::class, ['label' => 'Löschen']);
        $fb->add('save', SubmitType::class, ['label' => 'Speichern']);

        $form = $fb->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('delete')->isClicked()) {
                $this->service->remove($key);
            } else if ($form->get('save')->isClicked()) {
                $data = $form->getData();
                $this->service->setSettingsObject($data);
            }

            return $this->redirectToRoute('admin_setting', ['_fragment' => $key]);
        }

        return $this->render(!$request->isXmlHttpRequest() ? 'admin/settings/edit.html.twig' : 'admin/settings/edit.modal.html.twig', [
            'key' => $key,
            'desc' => SettingService::getDescription($key),
            'form' => $form->createView(),
        ]);
    }
}
