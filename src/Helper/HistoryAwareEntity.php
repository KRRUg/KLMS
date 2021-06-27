<?php

namespace App\Helper;

use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

interface HistoryAwareEntity
{
     public function setAuthorId(?UuidInterface $uuid);
     public function getAuthorId(): ?UuidInterface;
     public function setModifierId(?UuidInterface$uuid);
     public function getModifierId(): ?UuidInterface;

    public function getLastModified(): ?DateTimeInterface;
    public function setLastModified(?DateTimeInterface $last_modified);
    public function getCreated(): ?DateTimeInterface;
    public function setCreated(?DateTimeInterface $created);
}