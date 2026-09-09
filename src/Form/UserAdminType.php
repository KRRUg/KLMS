<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class UserAdminType extends UserType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('birthdate', BirthdayType::class, [
            'label' => 'Geburtsdatum',
            'widget' => 'single_text',
            'html5' => false,
            'format' => 'dd.MM.yyyy',
            'invalid_message' => 'Bitte im Format TT.MM.JJJJ eingeben.',
            'attr' => [
                'class' => 'form-control',
                'inputmode' => 'numeric',
                'placeholder' => 'TT.MM.JJJJ',
                'maxlength' => 10,
            ],
            'required' => false,
        ]);

        $builder->get('birthdate')->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event): void {
            $value = $event->getData();
            if (!is_string($value)) {
                return;
            }

            $digits = preg_replace('/\D+/', '', $value) ?? '';
            if (strlen($digits) !== 8) {
                return;
            }

            $event->setData(substr($digits, 0, 2).'.'.substr($digits, 2, 2).'.'.substr($digits, 4, 4));
        });
    }
}
