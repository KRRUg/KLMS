<?php

namespace App\Form;

use App\Entity\BeamerNews;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class BeamerNewsType extends AbstractType
{
    private readonly AuthorInsertSubscriber $userInsertSubscriber;

    public function __construct(AuthorInsertSubscriber $userInsertSubscriber)
    {
        $this->userInsertSubscriber = $userInsertSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titel',
            ])
            ->add('content', HtmlTextareaType::class, [
                'required' => false,
                'empty_data' => '',
                'label' => 'Inhalt',
            ])
            ->add('visible', CheckboxType::class, [
                'required' => false,
                'label' => 'Sichtbar',
            ])
        ;
        $builder->add('imageFile', VichImageType::class, [
            'required' => false,
            'allow_delete' => false,
            'download_uri' => false,
            'image_uri' => false,
            'asset_helper' => false,
            'imagine_pattern' => 'beamer_news_image',
            'label' => 'Bild',
            'help' => 'Das Bild wird quadratisch angezeigt und ggf. beschnitten. Erlaubte Formate: PNG, JPEG, WebP. Maximale Größe: 10 MB.',
            'attr' => [
                'accept' => 'image/png,image/jpeg,image/webp',
            ],
        ]);
        $builder->addEventSubscriber($this->userInsertSubscriber);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BeamerNews::class,
        ]);
    }
}
