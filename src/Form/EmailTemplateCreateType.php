<?php

namespace App\Form;

use App\Entity\Admin\EMail\EMailTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailTemplateCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('subject')
            ->add('body')
            //->add('last_modified')
            //->add('created')
            ->add('published')
            ->add('save', SubmitType::class, ["label" => "speichern"])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EMailTemplate::class,
        ]);
    }
}
