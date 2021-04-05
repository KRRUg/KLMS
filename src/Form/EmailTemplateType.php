<?php

namespace App\Form;

use App\Entity\EMailTemplate;
use App\Helper\AuthorInsertSubscriber;
use App\Service\EMailService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailTemplateType extends AbstractType
{
    private AuthorInsertSubscriber $userInsertSubscriber;

    public function __construct(AuthorInsertSubscriber $userInsertSubscriber)
    {
        $this->userInsertSubscriber = $userInsertSubscriber;
    }

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('name', TextType::class, ['label' => 'Name'])
			->add('subject', TextType::class, ['label' => 'Betreff'])
			->add('body', TextareaType::class, [
			    'label' => 'Inhalt',
                'required' => false,
                'empty_data' => '',
            ])
			->add('designFile', ChoiceType::class, [
                'label' => 'Design',
				'choices' => EMailService::NEWSLETTER_DESIGNS,
            ]);
        $builder->addEventSubscriber($this->userInsertSubscriber);
	}

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(['data_class' => EMailTemplate::class]);
	}
}
