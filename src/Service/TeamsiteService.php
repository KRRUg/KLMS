<?php

namespace App\Service;

use App\Repository\TeamsiteEntryRepository;
use App\Repository\TeamsiteRepository;

class TeamsiteService
{
    private TeamsiteRepository $repo;
    private TeamsiteEntryRepository $entry;

    public function getAll()
    {
        return $this->repo->findAll();
    }
}