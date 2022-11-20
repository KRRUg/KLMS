<?php

namespace App\Idm\Annotation;

use Attribute;
use Symfony\Contracts\Service\Attribute\Required;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Collection
{
    public function __construct(
        #[Required] public string $class
    ){}

    public function getClass(): string
    {
        return $this->class;
    }
}
