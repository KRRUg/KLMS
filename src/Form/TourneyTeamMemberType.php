<?php

namespace App\Form;

use App\Entity\TourneyTeamMember;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\UserSelectType;

class TourneyTeamMemberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('gamer', UserSelectType::class, [
                'label' => 'Spieler',
                'required' => true,
                'hydrate' => false,
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
        ]);
    }
}