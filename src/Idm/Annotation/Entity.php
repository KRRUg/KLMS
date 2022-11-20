<?php

namespace App\Idm\Annotation;

use Attribute;
use Symfony\Contracts\Service\Attribute\Required;

#[Attribute(Attribute::TARGET_CLASS)]
class Entity
{
    /**
     * @var string $path Path to the endpoint
     * @var bool $authorize Has an authorize endpoint
     * @var bool $search Has a search endpoint
     * @var bool $bulk Has a bulk endpoint
     */
    public function __construct(
        #[Required] public string $path,
        public bool $authorize = false,
        public bool $search = false,
        public bool $bulk = false
    ){}

    public function getPath(): string
    {
        return $this->path;
    }

    public function hasAuthorize(): bool
    {
        return $this->authorize;
    }

    public function hasSearch(): bool
    {
        return $this->search;
    }

    public function hasBulk(): bool
    {
        return $this->bulk;
    }
}
