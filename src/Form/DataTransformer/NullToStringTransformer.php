<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class NullToStringTransformer implements DataTransformerInterface
{


    /**
     * No Transformation for displaying the value in the Form
     * (Backend -> FormView)
     *
     */
    public function transform($value): ?string
    {
        return $value;
    }

    /**
     * Transforms a null to an empty String.
     * (FormView -> Backend)
     *
     */
    public function reverseTransform($value): ?string
    {
        // Convert null to '' so it gets handed over to IDM
        if (null === $value) {
            return '';
        }

        return $value;
    }
}
