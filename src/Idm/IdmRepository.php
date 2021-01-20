<?php

namespace App\Idm;

use InvalidArgumentException;
use ReflectionClass;

class IdmRepository
{
    private IdmManager $manager;
    private string $class;
    private ReflectionClass $reflection;

    public function __construct(IdmManager $manager, string $class)
    {
        $this->manager = $manager;
        $this->class = $class;
        $this->reflection = new ReflectionClass($class);
    }

    public function findOneById($id): ?object
    {
        return $this->manager->request($this->class, $id);
    }

    public function authenticate(string $name, string $secret): bool
    {
        return $this->manager->auth($this->class, $name, $secret);
    }

    /**
     * Use request() instead of findBy when searching for id only.
     * @param array $filter filter array [prop => value]
     * @param array $sort sorting array [prop => (asc|desc)]
     * @return mixed|null The first value found or null if no value was found
     */
    public function findOneBy(array $filter = [], array $sort = [])
    {
        $result = $this->manager->find($this->class, $filter, $sort, 0, 1);
        return $result->count >= 1 ? $result->items[0] : null;
    }

    /**
     * Use request() instead of find when searching for id only.
     */
    public function findBy(array $filter = [], array $sort = []): IdmPagedCollection
    {
        $this->checkProperties($filter, $sort);
        return IdmPagedCollection::create($this->manager, $this->class, $filter, $sort);
    }

    public function findFuzzy(string $query, array $sort = []): IdmPagedCollection
    {
        $this->checkProperties([], $sort);
        return IdmPagedCollection::create($this->manager, $this->class, $query, $sort);
    }

    public function findAll()
    {
        return $this->findBy();
    }

    /**
     * @param array $filter
     * @param array $sort
     */
    private function checkProperties(array $filter, array $sort): void
    {
        foreach ($filter as $prop => $value) {
            if (!$this->reflection->hasProperty($prop))
                throw new InvalidArgumentException("Property {$prop} is not in class {$this->class}");
        }
        foreach ($sort as $prop => $dir) {
            if (!$this->reflection->hasProperty($prop))
                throw new InvalidArgumentException("Property {$prop} is not in class {$this->class}");
            if (!($dir === 'asc' || $dir === 'desc'))
                throw new InvalidArgumentException("Invalid sort direction {$dir} for Property {$prop}");
        }
    }
}