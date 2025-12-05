<?php

declare(strict_types=1);

namespace App\Core;

class App
{
    public function __construct(
        private readonly Config $config,
        private readonly Router $router,
        private readonly Database $database
    ) {
        date_default_timezone_set((string)$this->config->get('app.timezone', 'UTC'));
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function database(): Database
    {
        return $this->database;
    }

    public function run(string $method, string $uri): void
    {
        $context = [
            'config' => $this->config,
            'db' => $this->database,
        ];

        $response = $this->router->dispatch($method, $uri, $context);
        $response->send();
    }
}
