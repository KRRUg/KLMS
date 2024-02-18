<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.service.wipe')]
interface WipeInterface
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
