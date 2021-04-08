<?php

namespace App\Form;

use App\Entity\EmailSending;
use App\Repository\EMailRepository;
use App\Service\EMailService;
use App\Service\GroupService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EMailSendingType extends AbstractType
{
    protected $templateRepository;
    protected $mailService;

	public function __construct(EMailRepository $templateRepository, EMailService $mailService)
    {
        $this->templateRepository = $templateRepository;
        $this->mailService = $mailService;
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
            ->add('recipientGroup', ChoiceType::class, [ 'choices' => GroupService::getGroups() ])
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
