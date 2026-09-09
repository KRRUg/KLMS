<?php

namespace App\Form;

use App\Entity\Tourney;
use App\Entity\TourneyRules;
use App\Service\TourneyService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Form\Type\VichFileType;

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
            ->add('mode', EnumType::class, [
                'label' => 'Modus',
                'class' => TourneyRules::class,
                'choice_label' => fn ($c) => $c->getMessage(),
                // On create we derive group modes from the checkbox, on edit we must still show stored group modes.
                'choice_filter' => fn ($c) => $c !== null && (!$options['create'] || !$c->requiresGroupStage()),
                'expanded' => true,
                'multiple' => false,
                'disabled' => !$options['create'],
            ])
            ->add('maxTeams', IntegerType::class, [
                'label' => 'Maximum Teams',
                'required' => false,
                'empty_data' => null,
                'disabled' => !$options['create'],
            ])
            ->add('hasGroupStage', CheckboxType::class, [
                'label' => 'Gruppenphase',
                'required' => false,
                'mapped' => false,
                'disabled' => !$options['create'],
                'attr' => [
                    'class' => 'group-stage-toggle',
                ],
                'help' => 'Teams spielen erst in Gruppen, bevor die K.o.-Phase beginnt.',
            ])
            ->add('groupCount', IntegerType::class, [
                'label' => 'Anzahl Gruppen',
                'required' => false,
                'empty_data' => null,
                'attr' => [
                    'min' => '1',
                    'class' => 'group-stage-field',
                ],
                'disabled' => !$options['create'],
            ])
            ->add('groupAdvance', IntegerType::class, [
                'label' => 'Teams pro Gruppe, die weiterkommen',
                'required' => false,
                'empty_data' => null,
                'attr' => [
                    'min' => '1',
                    'class' => 'group-stage-field',
                ],
                'disabled' => !$options['create'],
            ])
            ->add('rulesFile', VichFileType::class, [
                'label' => 'Regelwerk (PDF)',
                'required' => false,
                'allow_delete' => true,
                'delete_label' => 'Regelwerk löschen',
                'download_uri' => true,
            ])
        ;

        // Set initial value for hasGroupStage checkbox based on existing mode.
        // POST_SET_DATA ensures mapped child values are already initialized.
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $tourney = $event->getData();
            $form = $event->getForm();
            
            if ($tourney && $tourney->getMode() !== null) {
                $hasGroupStage = $tourney->getMode()->requiresGroupStage();
                $form->get('hasGroupStage')->setData($hasGroupStage);
            }
        });

        // Convert mode + hasGroupStage to correct TourneyRules enum
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $tourney = $event->getData();
            $form = $event->getForm();
            
            $hasGroupStage = $form->get('hasGroupStage')->getData();
            $mode = $tourney->getMode();
            
            if ($hasGroupStage && $mode) {
                $newMode = match($mode) {
                    TourneyRules::SingleElimination => TourneyRules::GroupSingleElimination,
                    TourneyRules::DoubleElimination => TourneyRules::GroupDoubleElimination,
                    default => $mode,
                };
                $tourney->setMode($newMode);
            }
        });

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
