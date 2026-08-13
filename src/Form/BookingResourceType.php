<?php

namespace App\Form;

use App\Entity\BookingResource;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class BookingResourceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('priority', IntegerType::class, [
                'label' => 'Reihenfolge',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'zb. Dusche',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Beschreibung',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                ],
            ])
            ->add('unitCount', IntegerType::class, [
                'label' => 'Anzahl Einheiten',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                ],
            ])
            ->add('unitNamesText', TextareaType::class, [
                'label' => 'Einzelnamen der Einheiten',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => "Eine Einheit pro Zeile\nzb. Dusche 1 Halle 17\nzb. Dusche 2 Halle 19",
                ],
            ])
            ->add('slotDurationMinutes', IntegerType::class, [
                'label' => 'Slot-Dauer (Minuten)',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                ],
            ])
            ->add('bufferMinutes', IntegerType::class, [
                'label' => 'Pufferzeit (Minuten)',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                ],
            ])
            ->add('availableFrom', DateTimeType::class, [
                'label' => 'Buchbar von',
                'constraints' => [
                    new NotNull(['message' => 'Bitte einen Startzeitpunkt angeben.']),
                ],
            ])
            ->add('availableUntil', DateTimeType::class, [
                'label' => 'Buchbar bis',
                'constraints' => [
                    new NotNull(['message' => 'Bitte einen Endzeitpunkt angeben.']),
                ],
            ])
            ->add('maxBookingsPerUser', IntegerType::class, [
                'label' => 'Max. Buchungen pro User',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'placeholder' => 'leer = unbegrenzt',
                ],
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Aktiv',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function ($event): void {
            $resource = $event->getData();
            if (!$resource instanceof BookingResource) {
                return;
            }

            $form = $event->getForm();
            $unitNames = $resource->getUnitNames();

            if (count($unitNames) > $resource->getUnitCount()) {
                $form->get('unitNamesText')->addError(new FormError('Es dürfen maximal so viele Namen gepflegt werden wie Einheiten vorhanden sind.'));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BookingResource::class,
        ]);
    }
}
