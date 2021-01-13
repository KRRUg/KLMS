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
     * @var string The subpath of the collection, defaults to the property name
     */
    public string $subpath;

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function getSubpath(): string
    {
        return $this->subpath;
    }
}