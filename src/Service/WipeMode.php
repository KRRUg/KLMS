<?php

namespace App\Service;

enum WipeMode
{
    case WIPE_SETTINGS;
    case WIPE_RESET;
    case WIPE_FULL;
}
