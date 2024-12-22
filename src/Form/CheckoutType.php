<?php

namespace App\Form;

use App\Entity\ShopAddon;
use App\Service\ShopService;
use App\Service\TicketService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['tickets']) {
            $max = $options['max_ticket_count_callback'] ? $options['max_ticket_count_callback']() : null;
            $builder
                ->add('tickets', IntegerType::class, [
                    'required' => false,
                    'empty_data' => "0",
                    'attr' => [
                        'min' => 0,
                        'max' => $max ?? ShopService::MAX_TICKET_COUNT,
                    ],
                    'constraints' => [
                        new Assert\GreaterThanOrEqual(0),
                        new Assert\LessThanOrEqual($max ?? ShopService::MAX_TICKET_COUNT)
                    ],
                ]);
        }
        if ($options['code']) {
            $builder
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
            $name = "addon{$addon->getId()}";
            $max = $options['max_addon_count_callback'] ? $options['max_addon_count_callback']($addon) : null;
            $form_opt = [];

            if (!is_null($max)) {
                if ($max < 0) {
                    $form_opt['help'] = "Du kannst dieses Add-On nicht (noch einmal) bestellen.";
                    $form_opt['disabled'] = true;
                } else if($max == 0) {
                    $form_opt['help'] = "Dieses Addon ist nicht mehr verfügbar.";
                    $form_opt['disabled'] = true;
                } else {
                    $form_opt['help'] = "Es sind nur noch {$max} Stück verfügbar.";
                    $form_opt['disabled'] = false;
                }
            }

            /** @var ShopAddon $addon */
            if ($addon->getOnlyOnce()) {
                $builder->add($name, CheckboxType::class, array_merge($form_opt, [
                    'required' => false,
                    'value' => "1",
                    'label' => "Hinzufügen",
                ]));
            } else {
                $max = max(0, $max ?? ShopService::MAX_TICKET_COUNT);
                $builder->add($name, IntegerType::class, array_merge($form_opt, [
                    'required' => false,
                    'empty_data' => "0",
                    'attr' => [
                        'min' => 0,
                        'max' => $max,
                    ],
                    'constraints' => [
                        new Assert\GreaterThanOrEqual(0),
                        new Assert\LessThanOrEqual($max)
                    ],
                ]));
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // *_count_callback (return type ?int)
        // return the maximum amount the user can buy, null if there is no maximum
        // or -1 if the user can't buy the item

        $resolver->setDefaults([
            'tickets' => true,
            'code' => true,
            'addons' => [],
            'max_ticket_count_callback' => null,
            'max_addon_count_callback' => null,
        ]);

        $resolver
            ->setAllowedTypes('tickets', 'bool')
            ->setAllowedTypes('code', 'bool')
            ->setAllowedTypes('addons', ShopAddon::class.'[]')
            ->setAllowedTypes('max_ticket_count_callback', ['null', 'callable'])
            ->setAllowedTypes('max_addon_count_callback', ['null', 'callable']);
    }
}
