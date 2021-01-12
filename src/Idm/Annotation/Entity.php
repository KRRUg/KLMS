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
    public $path;

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
}