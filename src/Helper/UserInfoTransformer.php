<?php


namespace App\Helper;


use App\Security\User;
use App\Security\UserInfo;
use App\Service\UserService;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class UserInfoTransformer implements DataTransformerInterface
{
    private $userService;

    /**
     * UserInfoTransformer constructor.
     * @param $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
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

        $data[$entity->getUuid()] = $entity->getUsername();
    }

    /**
     * @inheritDoc
     */
    public function reverseTransform($value)
    {
        $ret = $this->userService->getUserInfosByUuid([$value]);
        return $ret[0];
    }
}