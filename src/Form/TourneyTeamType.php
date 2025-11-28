<?php

namespace App\Form;

use App\Entity\TourneyTeam;
use App\Entity\TourneyTeamMember;
use App\Entity\User;
use App\Idm\IdmManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Form\AbstractType;
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

        if ($tourney->isSinglePlayer()) {
            $builder->add('singlePlayer', UserSelectType::class, [
                'label' => 'Spieler auswählen',
                'required' => true,
                'mapped' => false,
                'hydrate' => false,
            ]);
        } else {
            $builder->add('name', TextType::class, [
                'label' => 'Teamname',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Team Name eingeben...'
                ],
            ]);
            $builder->add('members', CollectionType::class, [
                'entry_type' => TourneyTeamMemberType::class,
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
                $selectedUser = $form->get('singlePlayer')->getData();

                if ($selectedUser) {
                    if ($selectedUser instanceof User) {
                        $user = $selectedUser;
                    } else {
                        $user = $userRepo->findOneById(Uuid::fromString((string) $selectedUser));
                    }

                    if ($user) {
                        $team->setName(null);

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