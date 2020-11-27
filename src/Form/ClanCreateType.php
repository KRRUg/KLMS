<?php

namespace App\Form;

use App\Transfer\ClanCreateTransfer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClanCreateType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('name')
            ->add('joinPassword', PasswordType::class)
            ->add('clantag')
            ->add('description')
            ->add('website')
            ->add('save', SubmitType::class, ['label' => 'Speichern'])

        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ClanCreateTransfer::class,
        ]);
    }
}
