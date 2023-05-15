<?php

namespace App\Idm;

use App\Idm\Annotation\Collection;
use App\Idm\Annotation\Reference;

final class DeepCompare
{
    // TODO move in extra class
    public static function diffObjects(object $a, object $b): ?array
    {
        $rslt = [];

        if ($a::class != $b::class) {
            return null;
        }

        $ref = new ReflectionClass($a);

        foreach ($ref->getProperties() as $property) {
            if ($property->getAttributes(Collection::class) || $property->getAttributes(Reference::class)) {
                continue;
            }
            $v_a = $property->getValue($a);
            $v_b = $property->getValue($b);
            if ($v_a !== $v_b) {
                $rslt[] = $property->getName();
            }
        }
        return $rslt;
    }

    // TODO move in extra class
    public static function compareObjects(object $a, object $b): bool
    {
        if ($a::class != $b::class) {
            return false;
        }

        $ref = new ReflectionClass($a);

        foreach ($ref->getProperties() as $property) {
            $v_a = $property->getValue($a);
            $v_b = $property->getValue($b);

            if ($property->getAttributes(Collection::class)) {
                if (!self::compareCollections($v_a, $v_b)) {
                    return false;
                }
            } elseif ($property->getAttributes(Reference::class)) {
                if (!self::compareObjects($v_a, $v_b)) {
                    return false;
                }
            } else {
                if ($v_a != $v_b) {
                    return false;
                }
            }
        }

        return true;
    }
}