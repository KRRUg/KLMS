<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactRequestType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('firstname', TextType::class)
			->add('surname', TextType::class)
			->add('email', EmailType::class, ['required' => true])
			->add('subject', TextType::class)
			->add('message', TextareaType::class)
			->add('save', SubmitType::class, ['label' => 'senden']);
	}

	public function configureOptions(OptionsResolver $resolver)
	{
		//$resolver->setDefaults([]);
	}
}
