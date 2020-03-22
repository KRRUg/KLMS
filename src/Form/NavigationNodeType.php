<?php

namespace App\Form;

use App\Entity\NavigationNode;
use App\Entity\NavigationNodeContent;
use App\Entity\NavigationNodeGeneric;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;


class NavigationNodeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name');

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $entry = $event->getData();
            $form = $event->getForm();

            if ($entry instanceof NavigationNodeContent) {
                $form->add('content', ContentType::class);
            } elseif ($entry instanceof NavigationNodeGeneric) {
                $form->add('path', TextType::class);
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
