<?php

namespace App\Form;

use App\Entity\Faq;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FaqType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('priority', IntegerType::class, [
                'label' => 'Reihenfolge',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0
                ]
            ])
            ->add('question', TextType::class, [
                'label' => 'Frage',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Wie kann ich mich registrieren?'
                ]
            ])
            ->add('answer', HtmlTextareaType::class, [
                'label' => 'Antwort',
                'fix_urls' => 'relative',
                'required' => false,
                'empty_data' => '',
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Aktiv',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Faq::class,
        ]);
    }
}