<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\NewsComment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewsCommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('content', TextareaType::class, [
            'label' => '<i class="fas fa-plus"></i> Neuen Kommentar erstellen',
            'label_html' => true,
            'attr' => [
                'rows' => 4,
                'placeholder' => 'Teile deine Gedanken mit der Community...',
            ],
            'required' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NewsComment::class,
        ]);
    }
}
