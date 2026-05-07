<?php
declare(strict_types=1);

/**
 * Container - Simple Dependency Injection Container
 */
class Container
{
    private array $services = [];
    private array $instances = [];

    public function set(string $key, callable $factory): self
    {
        $this->services[$key] = $factory;
        return $this;
    }

    public function get(string $key)
    {
        if (!isset($this->instances[$key])) {
            if (isset($this->services[$key])) {
                $this->instances[$key] = ($this->services[$key])($this);
            } else {
                throw new Exception("Service not registered: $key");
            }
        }
        return $this->instances[$key];
    }

    public function has(string $key): bool
    {
        return isset($this->services[$key]) || isset($this->instances[$key]);
    }

    /**
     * Get a fresh instance (not cached) - useful for DB connections
     */
    public function make(string $key)
    {
        if (!isset($this->services[$key])) {
            throw new Exception("Service not registered: $key");
        }
        return ($this->services[$key])($this);
    }
}