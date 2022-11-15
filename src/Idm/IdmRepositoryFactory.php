<?php

namespace App\Idm;

class IdmRepositoryFactory
{
    private $repositoryList = [];

    public function getRepository(IdmManager $manager, string $class)
    {
        $repositoryHash = $class.spl_object_hash($manager);

        if (isset($this->repositoryList[$repositoryHash])) {
            return $this->repositoryList[$repositoryHash];
        }

        return $this->repositoryList[$repositoryHash] = new IdmRepository($manager, $class);
    }
}
