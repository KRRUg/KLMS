<?php

namespace App\Form;

use App\Entity\Content;
use App\Helper\AuthorInsertSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContentType extends AbstractType
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
            ->add('description', TextType::class, [
                'label' => 'Beschreibung',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Inhalt',
                'required' => false,
            ])
        ;
        $builder->addEventSubscriber($this->userInsertSubscriber);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Content::class,
        ]);
    }
}
