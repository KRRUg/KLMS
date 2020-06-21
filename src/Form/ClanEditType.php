<?php

namespace App\Form;

use App\Model\ClanModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClanEditType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        //Build the Choices Array and the selected Admins
        $choices = [];
        $admins = [];
        foreach ($options['data']->getUsers() as $user) {
            if($user->getAdmin()) {
                $admins[] = $user->getUser()->getUuid();
            }
            $choices[$user->getUser()->getNickname()] = $user->getUser()->getUuid();
        }

        $builder
            ->add('name')
            ->add('clantag')
            ->add('description')
            ->add('website')
            ->add('createdAt', DateTimeType::class, [
                'disabled' => true
            ])
            ->add('admins', ChoiceType::class, [
                'choices' => $choices,
                'data' => $admins,
                'mapped' => false,
                'multiple' => true
            ])
            ->add('save', SubmitType::class, ['label' => 'Speichern'])

        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ClanModel::class,
        ]);
    }
}
