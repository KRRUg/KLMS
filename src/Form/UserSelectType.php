<?php

namespace App\Form;

use App\Entity\User;
use App\Idm\Exception\PersistException;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
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

    public function __construct(IdmManager $manager)
    {
        $this->userRepository = $manager->getRepository(User::class);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer(
            new CallbackTransformer(
                $this->transform(...),
                $options['hydrateUser'] ? $this->reverseTransform(...) : $this->reverseTransformUuid(...)
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'multiple' => false,
            'compound' => false,
            'hydrateUser' => true,
        ]);
        $resolver->setAllowedTypes('hydrateUser', 'bool');
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
            case $entity instanceof UuidInterface:
                $user = $this->userRepository->findOneById($entity);
                $data[$entity->toString()] = $user->getEmail();
                break;
            default:
                throw new TransformationFailedException('Unknown type to convert');
        }

        return $data;
    }

    public function reverseTransform($value): ?User
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

    public function reverseTransformUuid($value): ?UuidInterface
    {
        if (empty($value)) {
            return null;
        }
        return $value instanceof UuidInterface ? $value : Uuid::fromString($value);
    }
}
