<?php


namespace App\Form;

use Symfony\Component\OptionsResolver\OptionsResolver;

class DateTimeType extends \Symfony\Component\Form\Extension\Core\Type\DateTimeType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'widget' => 'single_text',
            'with_seconds' => false,
            'html5' => false,
        ]);
    }
}