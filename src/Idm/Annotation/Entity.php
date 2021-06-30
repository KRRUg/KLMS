<?php


namespace App\Idm\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Entity
{
    /**
     * @Required
     *
     * @var string
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

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return bool
     */
    public function hasAuthorize(): bool
    {
        return $this->authorize;
    }

    /**
     * @return bool
     */
    public function hasSearch(): bool
    {
        return $this->search;
    }

    /**
     * @return bool
     */
    public function hasBulk(): bool
    {
        return $this->bulk;
    }
}