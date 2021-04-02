<?php

namespace App\Form;

use App\Entity\Content;
use App\Entity\NavigationNode;
use App\Entity\NavigationNodeContent;
use App\Entity\NavigationNodeGeneric;
use App\Repository\ContentRepository;
use App\Service\NavigationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;


class NavigationNodeType extends AbstractType
{
    private ContentRepository $contentRepository;

    public function __construct(ContentRepository $contentRepository)
    {
        $this->contentRepository = $contentRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name');

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $entry = $event->getData();
            $form = $event->getForm();

            if ($entry instanceof NavigationNodeContent) {
                $form->add('content', ChoiceType::class, [
                    'choices' => $this->contentRepository->findAll(),
                    'choice_label' => function(?Content $content) {
                        return $content ? "{$content->getTitle()} ({$content->getId()})" : '';
                    },
                    'choice_value' => function(?Content $content) {
                        return $content ? "/content/{$content->getId()}" : '';
                    },
                    'multiple' => false,
                    'expanded' => false,
                ]);
            } elseif ($entry instanceof NavigationNodeGeneric) {
                $form->add('path', TextType::class, [
                    'attr' => ['pattern' => NavigationService::URL_REGEX],
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NavigationNode::class,
        ]);
    }
}
