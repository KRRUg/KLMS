<?php

namespace App\Service;

use App\Service\WipeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class WipeService
{
    /** @var WipeInterface[] */
    private array $wipeableServices;

    private EntityManagerInterface $em;

    public function __construct(
        #[TaggedIterator('app.service.wipe')]
        iterable $services,
        EntityManagerInterface $em
    ){
        $this->wipeableServices = [];
        foreach ($services as $service) {
            $this->wipeableServices[$service::class] = $service;
        }
        $this->em = $em;
    }

    public function getWipeableServiceIds(): array
    {
        return array_keys($this->wipeableServices);
    }

    private function checkServiceIds(array $serviceIds): bool
    {
        $ids = $this->getWipeableServiceIds();
        // check for subset
        return array_intersect($serviceIds, $ids) == $serviceIds;
    }

    /** @return string[]|false */
    public function sortDependencies(array $serviceIds): array|false
    {
        $dependsOnMe = array_fill_keys($serviceIds, []);
        foreach ($this->wipeableServices as $service) {
            foreach ($service->resetBefore() as $dependency) {
                if (!array_key_exists($dependency, $dependsOnMe)) {
                    return false;
                } else {
                    $dependsOnMe[$dependency][$service::class] = true;
                }
            }
        }

        $sorted = [];
        while (count($dependsOnMe) > 0) {
            $found = "";
            foreach ($dependsOnMe as $key => &$array) {
                if (count($array) == 0) {
                    $found = $key;
                    break;
                }
            }
            if (!$found) {
                return false;
            }
            $sorted[] = $found;
            foreach ($dependsOnMe as &$array) {
                unset($array[$found]);
            }
            unset($dependsOnMe[$found]);
        }
        return $sorted;
    }

    /** @param string[]|null $servicesToWipe */
    public function wipe(?array $servicesToWipe = null): void
    {
        if (is_null($servicesToWipe)) {
            // wipe all services
            $servicesToWipe = $this->getWipeableServiceIds();
        } else {
            // check if specified services are ok and wipe those
            if ($this->checkServiceIds($servicesToWipe)) {
                throw new \LogicException("Invalid service specified for wipe");
            }
        }

        $order = $this->sortDependencies($servicesToWipe);
        if ($order === false) {
            throw new \LogicException("Cyclic Service dependency detected");
        }

        $this->em->beginTransaction();
        foreach ($order as $id) {
            $this->wipeableServices[$id]->reset();
        }
        $this->em->commit();
    }
}
