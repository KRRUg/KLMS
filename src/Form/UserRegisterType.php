<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserRegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'E-Mail Adresse',
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options'  => ['label' => 'Kennwort'],
                'second_options' => ['label' => 'Kennwort bestätigen'],
                'invalid_message' => 'Die Kennwörter stimmen nicht überein.',
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Vorname'
            ])
            ->add('surname', TextType::class, [
                'label' => 'Nachname'
            ])
            ->add('nickname', TextType::class, [
                'label' => 'Nickname',
            ])
            ->add('infoMails', CheckboxType::class, [
                'label' => 'Newsletter abonnieren. Weitere Infos zum Newsletter findest du in unserer Datenschutzbestimmung <TODO: LINK EINFÜGEN>',
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
