<?php

namespace App\Form;

use App\Exception\UserServiceException;
use App\Security\User;
use App\Security\UserInfo;
use App\Service\UserService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserSelectType extends AbstractType implements DataTransformerInterface
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
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

        if (!($entity instanceof UserInfo || $entity instanceof User)) {
            throw new TransformationFailedException('Unknown type to convert');
        }

        $data[$entity->getUuid()] = $entity->getEmail();
        return $data;
    }

    /**
     * @inheritDoc
     */
    public function reverseTransform($value)
    {
        try {
            return $this->userService->getUserInfosByUuid([$value])[0];
        } catch (UserServiceException $e) {
            throw new TransformationFailedException('Unknown type to convert');
        }
    }
}
