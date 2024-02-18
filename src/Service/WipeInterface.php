<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.service.wipe')]
interface WipeInterface
{
    /**
     * Performs the wipe. All classes specified by wipeBefore() must be guaranteed to be reset before.
     */
    public function wipe(): void;

    /**
     * @return string[] A list of Classes that need to be wiped before this can be reset.
     */
    public function wipeBefore(): array;
}
