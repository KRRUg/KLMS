<?php

namespace App\Form;

use App\Entity\EMail\EMailSending;
use App\Entity\EMail\EMailTemplate;
use App\Repository\EMail\EMailTemplateRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EMailSendingType extends AbstractType
{
	protected $templateRepository;

	public function __construct(EMailTemplateRepository $templateRepository)
	{
		$this->templateRepository = $templateRepository;
	}

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			/*->add('EMailTemplate', EntityType::class, [
				'label' => 'E-Mail Vorlage',
				'class' => EMailTemplate::class,
				'query_builder' => $this->templateRepository->createQueryBuilderNewsletterTemplates(),
				'choice_label' => 'name'
			])*/
			->add('recipientGroup')
			//->add('status')
			//->add('created')
			->add('startTime')
			//->add('recipientCount')
			//->add('recipientCountSent')
			//->add('recipientCountGenerated')
			//->add('isPublished')
			->add('save', SubmitType::class, ["label" => "speichern"]);
	}

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			                       'data_class' => EMailSending::class,
		                       ]);
	}
}
