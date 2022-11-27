<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\UserGamer;
use App\Idm\Exception\PersistException;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Repository\UserGamerRepository;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserSelectType extends AbstractType
{
    private readonly IdmRepository $userRepository;
    private readonly UserGamerRepository $gamerRepository;

    public function __construct(IdmManager $manager, UserGamerRepository $gamerRepository)
    {
        $this->userRepository = $manager->getRepository(User::class);
        $this->gamerRepository = $gamerRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        switch ($options['type']) {
            case User::class:
                $builder->addViewTransformer(new CallbackTransformer($this->transform(...), $this->reverseTransformUser(...)));
                break;
            case UserGamer::class:
                $builder->addViewTransformer(new CallbackTransformer($this->transform(...), $this->reverseTransformGamer(...)));
                break;
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'multiple' => false,
            'compound' => false,
            'type' => User::class,
        ]);

        $resolver
            ->setAllowedTypes('type', 'string')
            ->setAllowedValues('type', [User::class, UserGamer::class])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'select2';
    }

    public function transform($entity): array
    {
        $data = [];
        if (empty($entity)) {
            return $data;
        }

        switch (true) {
            case $entity instanceof User:
                $data[$entity->getUuid()->toString()] = $entity->getEmail();
                break;
            case $entity instanceof UserGamer:
                $email = $this->userRepository->findOneById($entity->getUuid())->getEmail();
                $data[$entity->getUuid()->toString()] = $email;
                break;
            default:
                throw new TransformationFailedException('Unknown type to convert');
        }

        return $data;
    }

    public function reverseTransformUser($value): ?object
    {
        if (empty($value)) {
            return null;
        }

        $value = $value instanceof UuidInterface ? $value : Uuid::fromString($value);
        try {
            return $this->userRepository->findOneById($value);
        } catch (PersistException) {
            throw new TransformationFailedException('Unknown type to convert');
        }
    }

    public function reverseTransformGamer($value): ?UserGamer
    {
        if (empty($value)) {
            return null;
        }

        $value = $value instanceof UuidInterface ? $value : Uuid::fromString($value);
        try {
            return $this->gamerRepository->findOneBy(['uuid' => $value]);
        } catch (PersistException) {
            throw new TransformationFailedException('Unknown type to convert');
        }
    }
}
