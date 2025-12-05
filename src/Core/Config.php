<?php

declare(strict_types=1);

namespace App\Core;

class Config
{
    private array $data;

    public function __construct(string $configPath)
    {
        if (!is_file($configPath)) {
            throw new \InvalidArgumentException("Config file not found: {$configPath}");
        }

        $this->data = require $configPath;
        if (!is_array($this->data)) {
            throw new \RuntimeException('Config file must return an array');
        }
    }

    public static function loadDefault(string $basePath): self
    {
        return new self($basePath . '/config/config.php');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        $segments = explode('.', $key);
        $value = $this->data;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public function has(string $key): bool
    {
        $sentinel = new \stdClass();

        return $this->get($key, $sentinel) !== $sentinel;
    }

    public function all(): array
    {
        return $this->data;
    }
}
