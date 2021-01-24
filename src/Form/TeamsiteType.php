<?php

namespace App\Form;

use App\Entity\Teamsite;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TeamsiteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('title', TextType::class, [
                'required' => true,
                'label' => "Titel",
            ])
            ->add('description', TextareaType::class, [
                'required' => true,
                'empty_data' => '',
                'label' => "Beschreibung",
            ])
            ->add('content', HiddenType::class, [
                'mapped' => false,
                'required' => true,
                'constraints' => [new Assert\Json()],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Teamsite::class,
        ]);
    }
}
