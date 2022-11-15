<?php

namespace App\Idm\Annotation;

/**
 * @Annotation
 *
 * @Target("PROPERTY")
 */
class Collection
{
    /**
     * @Required
     */
    public string $class;

    public function getClass(): string
    {
        return $this->class;
    }
}
