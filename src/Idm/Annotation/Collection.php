<?php

namespace App\Idm\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Collection
{
    /**
     * @Required
     *
     * @var string
     */
    public string $class;

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }
}