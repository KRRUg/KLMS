<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'disabled' => true
            ])
            ->add('nickname')
            ->add('firstname', TextType::class, [
                'label' => 'Vorname'
            ])
            ->add('surname', TextType::class, [
                'label' => 'Nachname'
            ])
            ->add('birthdate', BirthdayType::class, [
                'label' => "Geburtsdatum",
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('postcode', TextType::class, [
                'required' => false,
                'label' => 'PLZ',
            ])
            ->add('city', TextType::class, [
                'label' => "Ort",
                'required' => false
            ])
            ->add('street', TextType::class, [
                'label' => "Straße",
                'required' => false
            ])
            ->add('country', CountryType::class, [
                'label' => "Land",
                'required' => false,
                'choice_translation_locale' => 'de',
                'preferred_choices' => ['AT', 'DE', 'CH'],
            ])
            ->add('phone', TextType::class, [
                'label' => "Telefon",
                'required' => false,
            ])
            ->add('gender', ChoiceType::class, [
                'label' => "Geschlecht",
                'required' => false,
                'choices' => [
                    'Weiblich' => 'f',
                    'Männlich' => 'm',
                    'Divers' => 'x',
                ],
            ])
            ->add('website', UrlType::class, [
                'required' => false,
            ])
            ->add('steamAccount', TextType::class, [
                'required' => false,
            ])
            ->add('hardware', TextType::class, [
                'required' => false,
            ])
            ->add('statements', TextType::class, [
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
