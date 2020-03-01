<?php

namespace App\Form;

use App\Entity\Admin\EMail\EMailSending;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EMailSendingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            //->add('name')
            //->add('created')
            //->add('last_modified')
            ->add('ready_to_send', CheckboxType::class, ["label" => "Sendungsfreigabe",
                //"attr" => ["checked" => true],
                'required' => false])
            //->add('emailSendingTasks')
            //->add('template')
            ->add('applicationHook')
            ->add('sendToAllUsers', ChoiceType::class, ['choices' => ['Alle IDM User' => 'ALL', 'KLMS Instanz' => 'KLMS'], 'mapped' => false])
            ->add('save', SubmitType::class, ["label" => "Sendung speichern"]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EMailSending::class,
        ]);
    }
}
