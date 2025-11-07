<?php

namespace App\Form;

use App\Entity\ClanDiscount;
use App\Entity\Clan;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;

class ClanDiscountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('clanId', \App\Form\ClanSelectType::class, [
                'label' => 'Clan',
                'required' => true,
                'hydrate' => false,
            ])
            ->add('price', IntegerType::class, [
            'label' => 'Preis in Cent',
            'required' => true,
            'constraints' => [
                new GreaterThan([
                    'value' => 0,
                    'message' => 'Der Preis muss größer als 0 sein.',
                ]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ClanDiscount::class,
        ]);
    }
}
