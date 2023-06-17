<?php

namespace App\Tests;

class IdmServerMockRequest
{
    public function __construct(
        public readonly string $class,
        public readonly string $id,
        public readonly array  $params,
        public readonly array $body,
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