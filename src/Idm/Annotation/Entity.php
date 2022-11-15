<?php

namespace App\Idm\Annotation;

/**
 * @Annotation
 *
 * @Target("CLASS")
 */
class Entity
{
    /**
     * @Required
     */
    public string $path;

    /**
     * @var bool Has an authorize endpoint
     */
    public bool $authorize = false;

    /**
     * @var bool Has a search endpoint
     */
    public bool $search = false;

    /**
     * @var bool Has a bulk endpoint
     */
    public bool $bulk = false;

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
