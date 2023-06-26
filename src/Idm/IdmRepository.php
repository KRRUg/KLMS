<?php

namespace App\Idm;

use App\Idm\Exception\PersistException;
use App\Idm\Exception\UnsupportedClassException;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

class IdmRepository
{
    private readonly IdmManager $manager;
    private readonly string $class;
    private readonly ReflectionClass $reflection;

    public function __construct(IdmManager $manager, string $class)
    {
        $this->manager = $manager;
        $this->class = $class;
        try {
            $this->reflection = new ReflectionClass($class);
        } catch (ReflectionException) {
            throw new UnsupportedClassException();
        }
    }

    public function findOneById($id): ?object
    {
        if (!ObjectInspector::isValidId($id, $this->class)) {
            return null;
        }
        try {
            return $this->manager->request($this->class, $id);
        } catch (PersistException $e) {
            if ($e->getCode() === PersistException::REASON_NOT_FOUND) {
                return null;
            }
            throw $e;
        }
    }

    public function findById(array $ids): array
    {
        try {
            return $this->manager->bulk($this->class, $ids);
        } catch (UnsupportedClassException) {
            return array_map(fn ($id) => $this->findOneById($id), $ids);
        }
    }

    public function authenticate(string $name, string $secret): bool
    {
        return $this->manager->auth($this->class, $name, $secret);
    }

    /**
     * Use request() instead of findBy when searching for id only.
     *
     * @param array $filter filter array [prop => value]
     * @param array $sort   sorting array [prop => (asc|desc)]
     *
     * @return mixed|null The first value found or null if no value was found
     */
    public function findOneBy(array $filter = [], array $sort = []): mixed
    {
        $result = $this->manager->find($this->class, $filter, false, true, $sort, 0, 1);

        return $result->count >= 1 ? $result->items[0] : null;
    }

    /**
     * Use request() instead of findBy when searching for id only.
     *
     * @param array $filter filter array [prop => value]
     * @param array $sort   sorting array [prop => (asc|desc)]
     *
     * @return mixed|null The first value found or null if no value was found
     */
    public function findOneCiBy(array $filter = [], array $sort = []): mixed
    {
        $result = $this->manager->find($this->class, $filter, false, false, $sort, 0, 1);

        return $result->count >= 1 ? $result->items[0] : null;
    }

    /**
     * Use request() instead of find when searching for id only.
     */
    public function findBy(array $filter = [], array $sort = []): IdmPagedCollection
    {
        $this->checkProperties($filter, $sort);

        return IdmPagedCollection::create($this->manager, $this->class, $filter, false, true, $sort);
    }

    public function findCiBy(array $filter = [], array $sort = []): IdmPagedCollection
    {
        $this->checkProperties($filter, $sort);

        return IdmPagedCollection::create($this->manager, $this->class, $filter, false, false, $sort);
    }

    public function findFuzzy(string $query, array $sort = []): IdmPagedCollection
    {
        $this->checkProperties([], $sort);

        return IdmPagedCollection::create($this->manager, $this->class, $query, true, false, $sort);
    }

    public function findAll(): Collection
    {
        return $this->findBy();
    }

    private function checkProperties(array $filter, array $sort): void
    {
        foreach ($filter as $prop => $value) {
            if (!$this->reflection->hasProperty($prop)) {
                throw new InvalidArgumentException("Property $prop is not in class $this->class");
            }
        }
        foreach ($sort as $prop => $dir) {
            if (!$this->reflection->hasProperty($prop)) {
                throw new InvalidArgumentException("Property $prop is not in class $this->class");
            }
            if (!($dir === 'asc' || $dir === 'desc')) {
                throw new InvalidArgumentException("Invalid sort direction $dir for Property $prop");
            }
        }
    }
}
