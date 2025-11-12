<?php

namespace App\Form;

use App\Entity\Poll;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class PollType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('question', TextType::class, [
                'label' => 'Frage',
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(message: 'Bitte gib eine Frage ein.'),
                    new Length(max: 255, maxMessage: 'Die Frage darf maximal {{ limit }} Zeichen enthalten.'),
                ],
            ])
            ->add('startAt', DateTimeType::class, [
                'label' => 'Anzeigen ab',
                'input' => 'datetime_immutable',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('endAt', DateTimeType::class, [
                'label' => 'Anzeigen bis',
                'input' => 'datetime_immutable',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('onlyRegistered', CheckboxType::class, [
                'label' => 'Nur für registrierte Nutzer:innen',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Poll::class,
        ]);
    }
}
