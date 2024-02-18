<?php

namespace App\Service;

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

    private function checkServiceIds(array $serviceIds): void
    {
        $ids = $this->getWipeableServiceIds();
        // check for subset
        if (!array_intersect($serviceIds, $ids) == $serviceIds) {
            throw new \LogicException("Invalid service specified for wipe");
        }
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
            $this->checkServiceIds($servicesToWipe);
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

    public function getAllDependenciesOfService(string $serviceId): array
    {
        $result = [];
        $todo = [$serviceId];
        while (count($todo) > 0) {
            $currentId = array_pop($todo);
            if (!array_key_exists($currentId, $result)) {
                $this->checkServiceIds([$currentId]);
                $result[$currentId] = true;
                array_push($todo, ...$this->wipeableServices[$currentId]->resetBefore());
            }
        }
        return array_keys($result);
    }
}
