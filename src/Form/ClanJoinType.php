<?php

namespace App\Form;

use App\Service\UserService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClanJoinType extends AbstractType
{
    /**
     * @var UserService
     */
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', ChoiceType::class, [
                'multiple' => false,
                'expanded' => false,
                'attr' => [
                    'class' => 'select2-enable',
                    'data-remote-target' => $options['data-remote-target'],
                    'data-label' => 'Clan suchen...',
                ],
                'choices' => $options['data'],
            ])
            ->add('joinPassword', PasswordType::class)
            ->add('save', SubmitType::class, ['label' => 'Clan beitreten'])
        ;

        // This is needed so we set the Choices on the ChoiceType, otherwise the Validator will fail
        // (alternatively make a ChoiceLoader that preloads all Clans; less efficient)
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            if (!$data) {
                return;
            }

            if ($this->userService->getClan($data['name'], true)) {
                $form->remove('name');
                $form->add('name', ChoiceType::class, [
                    'multiple' => false,
                    'expanded' => false,
                    'data' => $data['name'],
                    'choices' => [$data['name']],
                ]);
            } else {
                return;
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data-remote-target' => '',
        ]);
        $resolver->setAllowedTypes('data-remote-target', 'string');
    }
}
