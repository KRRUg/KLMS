<?php

namespace App\Form;

use App\Entity\News;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class NewsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('content', TextareaType::class, ['required' => false, 'empty_data' => ''])
            ->add('publishedFrom', DateTimeType::class, [
                'with_seconds' => false,
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('publishedTo', DateTimeType::class, [
                'with_seconds' => false,
                'widget' => 'single_text',
                'required' => false,
            ])
        ;
        $builder->add('imageFile', VichImageType::class, [
            'required' => false,
            'allow_delete' => true,
            'download_uri' => true,
            'image_uri' => true,
            'asset_helper' => true
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => News::class,
        ]);
    }
}
