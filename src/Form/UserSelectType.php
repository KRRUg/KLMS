<?php

namespace App\Form;

use App\Entity\User;
use App\Idm\Exception\PersistException;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserSelectType extends AbstractType implements DataTransformerInterface
{
    private IdmRepository $userRepository;

    public function __construct(IdmManager $manager)
    {
        $this->userRepository = $manager->getRepository(User::class);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer($this);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'multiple' => false,
            'compound' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'select2';
    }

    /**
     * @inheritDoc
     */
    public function transform($entity)
    {
        $data = array();
        if (empty($entity)) {
            return $data;
        }

        if (!($entity instanceof User)) {
            throw new TransformationFailedException('Unknown type to convert');
        }

        $data[$entity->getUuid()->toString()] = $entity->getEmail();
        return $data;
    }

    /**
     * @inheritDoc
     */
    public function reverseTransform($value)
    {
        $value = $value instanceof UuidInterface ? $value : Uuid::fromString($value);
        try {
            return $this->userRepository->findOneById($value);
        } catch (PersistException $e) {
            throw new TransformationFailedException('Unknown type to convert');
        }
    }
}
