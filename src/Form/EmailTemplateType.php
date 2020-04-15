<?php

namespace App\Form;

use App\Entity\EMail\EMailTemplate;
use App\Service\EMailService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Entity\File;

class EmailTemplateType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('name')
			->add('subject')
			->add('body')->setRequired(false)->setEmptyData('')
			//->add('last_modified')
			//->add('created')
			->add('designFile', ChoiceType::class, [
				'choices' => EMailService::NEWSLETTER_DESIGNS,
				'label' => 'Designfile'
			])
			->add('applicationHook', ChoiceType::class, [
				'choices' => EMailService::APPLICATIONHOOK_DESIGNS])
			->add('isPublished')
			->add('save', SubmitType::class, ["label" => "speichern"]);
	}

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			                       'data_class' => EMailTemplate::class,
		                       ]);
	}
}
