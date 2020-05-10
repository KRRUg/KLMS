<?php

namespace App\Form\Admin;

use App\Security\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminUserEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('uuid', TextType::class, [
                'disabled' => true
            ])
            ->add('email', EmailType::class, [
                'disabled' => true
            ])
            ->add('isSuperadmin', CheckboxType::class, [
                'disabled' => true
            ])
            ->add('registeredAt', DateTimeType::class, [
                'disabled' => true
            ])
            ->add('modifiedAt', DateTimeType::class, [
                'disabled' => true
            ])
            ->add('nickname')
            ->add('id', null, [
                'label' => 'KLMS ID',
                'disabled' =>  true
            ])
            ->add('status')
            ->add('firstname')
            ->add('surname')
            ->add('postcode')
            ->add('city')
            ->add('street')
            ->add('country')
            ->add('phone')
            ->add('gender')
            ->add('emailConfirmed')
            ->add('website')
            ->add('steamAccount')
            ->add('hardware')
            ->add('infoMails')
            ->add('statements')
            ->add('save', SubmitType::class, ['label' => 'Speichern'])

        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
