<?php

namespace App\Form;

use App\Entity\Seat;
use App\Entity\SeatKind;
use App\Entity\SeatOrientation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SeatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['show_pos']) {
            $builder
                ->add('posX', null, [
                    'label' => 'Position X',
                ])
                ->add('posY', null, [
                    'label' => 'Position Y',
                ]);
        }
        $builder
            ->add('name', null, [
                'label' => 'Sitzplatz Name/Kommentar',
            ])
            ->add('sector', null, [
                'label' => 'Sektor',
            ])
            ->add('seatNumber', null, [
                'label' => 'Sitzplatz',
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Sitzplatztyp',
                'choices' => [
                    'Sitzplatz' => SeatKind::SEAT,
                    'Gesperrter Sitzplatz' => SeatKind::LOCKED,
                    'Information' => SeatKind::INFO,
                ],
            ])
            ->add('chairPosition', ChoiceType::class, [
                'label' => 'Sitzorientierung',
                'choices' => [
                    'Oben' => SeatOrientation::NORTH,
                    'Rechts' => SeatOrientation::EAST,
                    'Unten' => SeatOrientation::SOUTH,
                    'Links' => SeatOrientation::WEST,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Seat::class,
            'show_pos' => true,
        ]);
        $resolver->setAllowedTypes('show_pos', ['bool']);
    }
}
