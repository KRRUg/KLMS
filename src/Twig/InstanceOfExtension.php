<?php

namespace App\Twig;

use ReflectionClass;
use ReflectionException;
use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

class InstanceOfExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getTests(): array
    {
        return [
            new TwigTest('instanceof', $this->isInstanceOf(...)),
        ];
    }

    public function isInstanceOf($object, $class): bool
    {
        if (!is_object($object)) {
            return false;
        }
        try {
            $reflectionClass = new ReflectionClass($class);
            return $reflectionClass->isInstance($object);
        } catch (ReflectionException) {
            return false;
        }
    }
}
