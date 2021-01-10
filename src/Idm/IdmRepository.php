<?php

namespace App\Idm;

class IdmRepository
{
    private IdmManager $manager;
    private string $class;

    public function __construct(IdmManager $idmConnection, string $class)
    {
        $this->manager = $idmConnection;
        $this->class = $class;
    }

    public function findAll()
    {
//        $result = $this->idmConnection->get($this->getUrl());
//        return $this->deserializeCollection($result);
    }

    public function findOneById($id)
    {
        return $this->manager->request($this->class, $id);
    }

    public function __call(string $method, array $arguments)
    {
        if (0 === mb_strpos($method, 'findBy')) {
            $fieldName = mb_strtolower(mb_substr($method, 6));
            $methodName = 'findBy';
        } elseif (0 === mb_strpos($method, 'findOneBy')) {
            $fieldName = mb_strtolower(mb_substr($method, 9));
            $methodName = 'findOneBy';
        } else {
            throw new \BadMethodCallException('Undefined method \'' . $method . '\'. The method name must start with either findBy or findOneBy!');
        }

        if (empty($arguments)) {
            throw new \BadMethodCallException('You need to pass a parameter to ' . $method);
        }

        // TODO perform post search
    }
}