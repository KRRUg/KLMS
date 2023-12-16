<?php

namespace App\Service;

interface Resettable
{
    /**
     * Performs the reset. All classes specified by resetBefore() are guaranteed to be reset before.
     */
    public function reset(): void;

    /**
     * @return string[] A list of Classes that need to be reset before this can be reset.
     */
    public function resetBefore(): array;
}