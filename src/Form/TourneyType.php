<?php

namespace App\Form;

use App\Entity\Tourney;
use App\Entity\TourneyRules;
use App\Service\TourneyService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TourneyType extends AbstractType
{
    private readonly AuthorInsertSubscriber $userInsertSubscriber;

    public function __construct(AuthorInsertSubscriber $userInsertSubscriber)
    {
        $this->userInsertSubscriber = $userInsertSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name'
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Beschreibung',
                'empty_data' => '',
                'required' => false,
            ])
            ->add('hidden', ChoiceType::class, [
                'label' => 'Anzeigen',
                'choices'  => [
                    'Anzeigen' => false,
                    'Verstecken' => true,
                ],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('token', NumberType::class, [
                'label' => 'Preis (Token)',
                'empty_data' => 20,
                'html5' => true,
                'attr' => [
                    'min' => '0',
                    'max' => TourneyService::TOKEN_COUNT,
                ],
                'constraints' => [
                    new Assert\Range(min: 0, max: TourneyService::TOKEN_COUNT),
                ],
            ])
            ->add('order', NumberType::class, [
                'label' => 'Ordnungsnummer',
                'html5' => true,
            ])
            ->add('show_points', ChoiceType::class, [
                'label' => 'Punkte im Baum anzeigen',
                'choices'  => [
                    'Punkte anzeigen' => true,
                    'Nur Sieger anzeigen' => false,
                ],
                'expanded' => false,
                'multiple' => false,
                'empty_data' => false,
                'required' => true,
            ])
            ->add('teamsize', NumberType::class, [
                'label' => 'Teamgröße',
                'attr' => [
                    'min' => '1',
                ],
                'constraints' => [
                    new Assert\Positive(),
                ],
                'html5' => true,
                'empty_data' => 1,
                'disabled' => !$options['create'],
            ])
            ->add('mode', ChoiceType::class, [
                'label' => 'Modus',
                'choices'  => [
                    'Nur Anmeldung' => TourneyRules::RegistrationOnly,
                    'Single Elimination' => TourneyRules::SingleElimination,
                    'Double Elimination' => TourneyRules::DoubleElimination,
                ],
                'empty_data' => TourneyRules::SingleElimination,
                'expanded' => true,
                'multiple' => false,
                'disabled' => !$options['create'],
            ])
        ;

        $builder->addEventSubscriber($this->userInsertSubscriber);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tourney::class,
            'create' => true,
        ]);

        $resolver
            ->setAllowedTypes('create', 'bool')
        ;
    }
}
