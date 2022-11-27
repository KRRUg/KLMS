<?php

namespace App\Form;

use App\Entity\Clan;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('joinPassword', TextType::class, [
                'label' => 'Join Passwort',
                'required' => $options['require_password'],
                'help' => 'Muss min. 6 Zeichen lang sein.',
                'empty_data' => null,
                'attr' => [
                    'placeholder' => $options['require_password'] ? '' : 'Existierendes Passwort',
                ],
            ])
            ->add('clantag', TextType::class, [
                'label' => 'Clan Tag',
                'help' => 'Darf bis zu 10 Zeichen lang sein.',
            ])
            ->add('description', TextareaType::class, [
                'empty_data' => '',
                'required' => false,
                'label' => 'Beschreibung',
            ])
            ->add('website', UrlType::class, [
                'empty_data' => '',
                'required' => false,
                'label' => 'Website',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Clan::class,
            'require_password' => false,
        ]);
        $resolver->setAllowedTypes('require_password', 'bool');
    }
}
