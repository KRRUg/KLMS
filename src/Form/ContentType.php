<?php

namespace App\Form;

use App\Entity\Content;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContentType extends AbstractType
{
    private AuthorInsertSubscriber $userInsertSubscriber;
    private HtmlHandlingSubscriber $htmlHandlingSubscriber;

    public function __construct(AuthorInsertSubscriber $userInsertSubscriber,
                                HtmlHandlingSubscriber $htmlHandlingSubscriber)
    {
        $this->userInsertSubscriber = $userInsertSubscriber;
        $this->htmlHandlingSubscriber = $htmlHandlingSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titel',
            ])
            ->add('alias', TextType::class, [
                'label' => 'Slug',
                'required' => false,
            ])
            ->add('description', TextType::class, [
                'label' => 'Beschreibung',
                'required' => false,
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Inhalt',
                'empty_data' => '',
                'required' => false,
            ])
        ;
        $builder
            ->addEventSubscriber($this->userInsertSubscriber)
            ->addEventSubscriber($this->htmlHandlingSubscriber);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Content::class,
        ]);
    }
}
