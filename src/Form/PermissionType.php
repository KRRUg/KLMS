<?php

namespace App\Form;

use App\Service\PermissionService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PermissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$options['include_user']) {
            $builder->add('user', UserSelectType::class, [
                        'multiple' => false,
                        'required' => true,
                    ]
            );
        }

        $builder->add('perm', ChoiceType::class, [
                'choices' => array_combine(PermissionService::PERMISSIONS, PermissionService::PERMISSIONS),
                'expanded' => true,
                'multiple' => true,
                'label' => 'Berechtigungen',
                'required' => true,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'include_user' => false,
        ]);
    }
}
