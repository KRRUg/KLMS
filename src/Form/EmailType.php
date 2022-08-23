<?php

namespace App\Form;

use App\Entity\Email;
use App\Service\EmailService;
use App\Service\GroupService;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailType extends AbstractType
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
            ->add('recipientGroup', ChoiceType::class, [
                'label' => 'Empfänger (Gruppe)',
                'placeholder' => '',
                'required' => false,
                'choices' => GroupService::getGroups(),
                'choice_value' => function (?UuidInterface $uuid) { return is_null($uuid) ? null : $uuid->toString(); },
            ])
            ->add('body', TextareaType::class, [
			    'label' => 'Inhalt',
                'empty_data' => '',
                'required' => false,
            ])
			->add('designFile', ChoiceType::class, [
                'label' => 'Design',
				'choices' => array_combine(array_keys(EmailService::NEWSLETTER_DESIGNS), array_keys(EmailService::NEWSLETTER_DESIGNS)),
            ]);
        if ($options['generate_buttons']) {
            $builder
                ->add('send', SubmitType::class)
                ->add('save', SubmitType::class);
        }
        $builder->addEventSubscriber($this->userInsertSubscriber);
	}

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
		    'data_class' => Email::class,
            'generate_buttons' => false,
        ]);
	}
}
