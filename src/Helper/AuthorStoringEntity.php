<?php

namespace App\Helper;

interface AuthorStoringEntity
{
     public function setAuthorId($uuid);
     public function getAuthorId();
     public function setModifierId($uuid);
     public function getModifierId();
}