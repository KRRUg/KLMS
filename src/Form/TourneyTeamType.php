<?php

namespace App\Form;

use App\Entity\TourneyTeam;
use App\Entity\TourneyTeamMember;
use App\Entity\User;
use App\Idm\IdmManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TourneyTeamType extends AbstractType
{
    private IdmManager $idmManager;

    public function __construct(IdmManager $idmManager)
    {
        $this->idmManager = $idmManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $tourney = $options['tourney'];
        $userRepo = $this->idmManager->getRepository(User::class);
        $allUsers = $userRepo->findAll();
        
        // Benutzer-Choices für Select-Felder
        $userChoices = [];
        foreach ($allUsers as $user) {
            $userChoices[$user->getNickname()] = $user->getUuid()->toString();
        }

        $builder
            ->add('name', TextType::class, [
                'label' => 'Teamname',
                'required' => !$tourney->isSinglePlayer(),
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $tourney->isSinglePlayer() ? 'Wird automatisch gesetzt' : 'Team Name eingeben...'
                ],
            ]);

        if ($tourney->isSinglePlayer()) {
            // Für 1vs1 nur einen Spieler auswählen
            $builder->add('singlePlayer', ChoiceType::class, [
                'label' => 'Spieler auswählen',
                'choices' => $userChoices,
                'placeholder' => 'Spieler auswählen...',
                'required' => true,
                'mapped' => false,
                'attr' => ['class' => 'form-control'],
            ]);
        } else {
            // Für Team-Turniere Collection von Team-Mitgliedern
            $builder->add('members', CollectionType::class, [
                'entry_type' => TourneyTeamMemberType::class,
                'entry_options' => [
                    'user_choices' => $userChoices,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'prototype_name' => '__name__',
            ]);
        }

        // Event Listener für automatischen Teamnamen bei 1vs1
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($tourney, $userRepo) {
            $team = $event->getData();
            $form = $event->getForm();
            
            if ($tourney->isSinglePlayer() && $form->has('singlePlayer')) {
                $selectedUserId = $form->get('singlePlayer')->getData();
                
                if ($selectedUserId) {
                    $user = $userRepo->findOneById(Uuid::fromString((string) $selectedUserId));
                    
                    if ($user) {
                        $team->setName($user->getNickname());
                        
                        // Team-Member erstellen oder aktualisieren
                        $member = $team->getMembers()->first();
                        if (!$member) {
                            $member = new TourneyTeamMember();
                            $member->setTeam($team);
                            $team->addMember($member);
                        }
                        $member->setGamer($user->getUuid());
                        $member->setAccepted(true);
                    }
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TourneyTeam::class,
            'tourney' => null,
        ]);
        
        $resolver->setRequired(['tourney']);
    }
}