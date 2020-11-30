<?php

namespace App\Helper;

interface HistoryAwareEntity
{
     public function setAuthorId($uuid);
     public function getAuthorId();
     public function setModifierId($uuid);
     public function getModifierId();

    public function getLastModified(): ?\DateTimeInterface;
    public function setLastModified(\DateTimeInterface $last_modified);
    public function getCreated(): ?\DateTimeInterface;
    public function setCreated(\DateTimeInterface $created);
}