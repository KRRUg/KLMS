<?php

namespace App\Service;

use App\Entity\User;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Repository\UserImageRepository;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class UserService
{
    private readonly IdmRepository $userRepo;
    private readonly UserImageRepository $imageRepo;
    private readonly UploaderHelper $uploadHelper;

    public function __construct(UserImageRepository $imageRepo, UploaderHelper $uploadHelper, IdmManager $manager)
    {
        $this->imageRepo = $imageRepo;
        $this->uploadHelper = $uploadHelper;
        $this->userRepo = $manager->getRepository(User::class);
    }

    public function getUserImage(User $user): ?string
    {
        $image = $this->imageRepo->findOneByUuid($user->getUuid());
        if (empty($image) || empty($image->getImage())) {
            return '';
        }

        return $this->uploadHelper->asset($image, 'imageFile');
    }

    public function user2Array(User $user): array
    {
        return [
            'uuid' => $user->getUuid(),
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nickname' => $user->getNickname(),
            'firstname' => $user->getFirstname(),
            'surname' => $user->getSurname(),
            'image' => $this->getUserImage($user),
            'clans' => array_map(fn($clan) => [
                'uuid' => $clan->getUuid(),
                'name' => $clan->getName(),
                'clantag' => $clan->getClantag(),
            ], $user->getClans()->toArray()),
        ];
    }

    public static function array2Uuid(array $a): ?UuidInterface
    {
        return array_key_exists('uuid', $a) && Uuid::isValid($a['uuid']) ? Uuid::fromString($a['uuid']) : null;
    }

    /**
     * Preloads multiple users to avoid multiple IDM requests.
     */
    public function preloadUsers(array $uuids)
    {
        $this->userRepo->findById($uuids);
    }
}
