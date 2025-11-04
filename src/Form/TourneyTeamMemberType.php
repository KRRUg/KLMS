<?php

namespace App\Form;

use App\Entity\TourneyTeamMember;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TourneyTeamMemberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $userChoices = $options['user_choices'] ?? [];

        $builder
            ->add('gamer', ChoiceType::class, [
                'label' => false,
                'choices' => $userChoices,
                'placeholder' => 'Spieler auswählen...',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('accepted', CheckboxType::class, [
                'label' => 'Bestätigt',
                'required' => false,
                'data' => true, // Standardmäßig bestätigt
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TourneyTeamMember::class,
            'user_choices' => [],
        ]);
    }
}