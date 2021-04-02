<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

class InstanceOfExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getTests()
    {
        return array(
            new TwigTest('instanceof', [$this, 'isInstanceOf'])
        );
    }

    public function isInstanceOf($object, $class)
    {
        if (!is_object($object)) {
            return false;
        }
        try {
            $reflectionClass = new \ReflectionClass($class);
            return $reflectionClass->isInstance($object);
        } catch (\ReflectionException $e) {
            return false;
        }
    }
}