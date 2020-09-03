<?php

namespace App\Helper;

interface AuthorStoringEntity
{
     public function setAuthorId($uuid);
     public function getAuthorId();
}