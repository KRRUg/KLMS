<?php

namespace App\Tests;

class IdmServerMockRequest
{
    public function __construct(
        public readonly string $class,
        public readonly string $uuid,
        public readonly array $params = [],
    )
    {}

    public function paramHasValue(string $param, string $value): bool
    {
        if ($this->paramNotExists($param)){
            return false;
        }
        return $this->params[$param] === $value;
    }

    public function paramNotExists(string $param): bool
    {
        return !array_key_exists($param, $this->params);
    }
}