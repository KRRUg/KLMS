<?php

namespace App\Form;

use App\Entity\Seat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SeatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('posX', null, [
                'label' => 'Position X',
            ])
            ->add('posY', null, [
                'label' => 'Position Y',
            ])
            ->add('name', null, [
                'label' => 'Sitzplatz Name/Kommentar'
            ])
            ->add('sector', null, [
                'label' => 'Sektor',
            ])
            ->add('seatNumber', null, [
                'label' => 'Sitzplatz',
            ])
            ->add('type', ChoiceType::class, [
                'label' => "Sitzplatztyp",
                'choices' => [
                    'Sitzplatz' => 'seat',
                    'Gesperrter Sitzplatz' => 'locked',
                    'Information' => 'information',
                ]
            ])
            ->add('chairPosition', ChoiceType::class, [
                'label' => 'Sitzorientierung',
                'choices' => [
                    'Oben' => 'top',
                    'Links' => 'left',
                    'Unten' => 'bottom',
                    'Rechts' => 'right',
                ]
            ])
            ->add('owner')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Seat::class,
        ]);
    }
}
