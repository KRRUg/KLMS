<?php

namespace App\Form;

use App\Entity\News;
use App\Helper\AuthorInsertSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class NewsType extends AbstractType
{
    private $userInsertSubscriber;

    public function __construct(AuthorInsertSubscriber $userInsertSubscriber)
    {
        $this->userInsertSubscriber = $userInsertSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titel',
            ])
            ->add('content', TextareaType::class, [
                'required' => false,
                'empty_data' => '',
                'label' => 'Inhalt',
            ])
            ->add('publishedFrom', DateTimeType::class, [
                'with_seconds' => false,
                'widget' => 'single_text',
                'required' => false,
                'label' => 'Anzeigen ab'
            ])
            ->add('publishedTo', DateTimeType::class, [
                'with_seconds' => false,
                'widget' => 'single_text',
                'required' => false,
                'label' => 'Anzeigen bis',
            ])
        ;
        $builder->add('imageFile', VichImageType::class, [
            'required' => false,
            'allow_delete' => false,
            'download_uri' => false,
            'image_uri' => false,
            'asset_helper' => false,
            'imagine_pattern' => 'news_header',
            'label' => 'Bild'
        ]);
        $builder->addEventSubscriber($this->userInsertSubscriber);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => News::class,
        ]);
    }
}
