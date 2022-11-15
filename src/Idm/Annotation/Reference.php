<?php

namespace App\Idm\Annotation;

/**
 * @Annotation
 *
 * @Target("PROPERTY")
 */
class Reference
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
