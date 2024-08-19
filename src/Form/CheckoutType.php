<?php

namespace App\Form;

use App\Entity\ShopAddon;
use App\Service\ShopService;
use App\Service\TicketService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CheckoutType extends AbstractType
{
    private const MAX_COUNT = ShopService::MAX_ADDON_COUNT;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['tickets']) {
            $builder
                ->add('tickets', IntegerType::class, [
                    'required' => false,
                    'empty_data' => 1,
                    'attr' => [
                        'min' => 0,
                        'max' => self::MAX_COUNT,
                    ],
                    'constraints' => [
                        new Assert\GreaterThanOrEqual(0),
                        new Assert\LessThanOrEqual(self::MAX_COUNT)
                    ]
                ])
                ->add('code', TextType::class, [
                    'required' => false,
                    'attr' => [
                        'pattern' => TicketService::CODE_REGEX,
                    ],
                    'constraints' => [
                        new Assert\Regex('/' . TicketService::CODE_REGEX . '/')
                    ]
                ]);
        }
        foreach ($options['addons'] as $addon) {
            /** @var ShopAddon $addon */
            $builder->add("addon{$addon->getId()}", IntegerType::class, [
                'required' => false,
                'empty_data' => 0,
                'attr' => [
                    'min' => 0,
                    'max' => self::MAX_COUNT,
                ],
                'constraints' => [
                    new Assert\GreaterThanOrEqual(0),
                    new Assert\LessThanOrEqual(self::MAX_COUNT)
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'tickets' => true,
            'addons' => []
        ]);
        $resolver
            ->setAllowedTypes('tickets', 'bool')
            ->setAllowedTypes('addons', ShopAddon::class.'[]');
    }
}
