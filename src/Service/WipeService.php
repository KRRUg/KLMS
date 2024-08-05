<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class WipeService
{
    /** @var WipeInterface[] */
    private array $wipeableServices;

    private readonly EntityManagerInterface $em;

    private readonly LoggerInterface $logger;

    public function __construct(
        #[TaggedIterator('app.service.wipe')]
        iterable $services,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ){
        $this->wipeableServices = [];
        foreach ($services as $service) {
            $this->wipeableServices[$service::class] = $service;
        }
        $this->em = $em;
        $this->logger = $logger;
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
    public function buildOrder(WipeMode $mode, array $serviceIds): array|false
    {
        $dependsOnMe = array_fill_keys($serviceIds, []);
        foreach ($serviceIds as $serviceId) {
            if (!isset($this->wipeableServices[$serviceId])) {
                return false;
            }
            $service = $this->wipeableServices[$serviceId];
            foreach ($service->wipeBefore() as $dependency) {
                if (!array_key_exists($dependency, $dependsOnMe)) {
                    return false;
                } else {
                    $dependsOnMe[$service::class][$dependency] = true;
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
    public function wipe(WipeMode $mode, ?array $servicesToWipe = null): bool
    {
        if (is_null($servicesToWipe)) {
            // wipe all services
            $servicesToWipe = $this->getWipeableServiceIds();
        } else {
            // check if specified services are ok and wipe those
            $this->checkServiceIds($servicesToWipe);
        }

        $order = $this->buildOrder($mode, $servicesToWipe);
        if ($order === false) {
            return false;
        }

        $this->em->beginTransaction();
        foreach ($order as $id) {
            $this->logger->info("Wiping service " . $id);
            $this->wipeableServices[$id]->wipe();
        }
        $this->em->commit();
        return true;
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
                array_push($todo, ...$this->wipeableServices[$currentId]->wipeBefore());
            }
        }
        unset($result[$serviceId]);
        return array_keys($result);
    }
}
