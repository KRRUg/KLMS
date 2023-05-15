<?php

namespace App\Idm;

use App\Idm\Annotation\Collection;
use App\Idm\Annotation\Reference;
use Ramsey\Uuid\Uuid;
use ReflectionClass;

final class ObjectInspector
{
    public static function object2Id(object $object)
    {
        // TODO change getUuid with id annotation
        return $object->getUuid();
    }

    public static function isValidId($id, string $class): bool
    {
        // TODO change getUuid with id annotation
        return Uuid::isValid(strval($id));
    }

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

    private static function compareCollections($a, $b): bool
    {
        $a = ($a instanceof LazyLoaderCollection) ? $a->toArray(false) : $a;
        $b = ($b instanceof LazyLoaderCollection) ? $b->toArray(false) : $b;

        if (!is_array($a) || !is_array($b)) {
            return false;
        }
        if (sizeof($a) != sizeof($b)) {
            return false;
        }

        $a = array_map(fn ($i_a) => self::object2Id($i_a), $a);
        $b = array_map(fn ($i_b) => self::object2Id($i_b), $b);

        return empty(array_diff($a, $b));
    }
}