<?php

declare(strict_types=1);

namespace App\Core;

use Exception;

class Response
{
    private string $body = '';
    private int $status = 200;
    private array $headers = [];
    private ?string $view = null;
    private array $data = [];

    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        if (str_ends_with($content, '.php')) {
            throw new Exception("Use Response::view() for templates.");
        }
        $this->body = $content;
        $this->status = $status;
        $this->headers = $headers;
    }
    
    public static function view(string $view, array $data = [], int $status = 200, array $headers = []): self
    {
        $response = new self('', $status, $headers);
        $response->view = $view;
        $response->data = $data;
        $response->headers = array_merge(['Content-Type' => 'text/html; charset=utf-8'], $headers);
        return $response;
    }

    public static function json(array $data, int $status = 200, array $headers = []): self
    {
        $headers = array_merge(['Content-Type' => 'application/json; charset=utf-8'], $headers);

        return new self(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $status, $headers);
    }

    public static function html(string $html, int $status = 200, array $headers = []): self
    {
        $headers = array_merge(['Content-Type' => 'text/html; charset=utf-8'], $headers);

        return new self($html, $status, $headers);
    }

    public static function text(string $text, int $status = 200, array $headers = []): self
    {
        $headers = array_merge(['Content-Type' => 'text/plain; charset=utf-8'], $headers);

        return new self($text, $status, $headers);
    }

    public static function xml(string $xml, int $status = 200, array $headers = []): self
    {
        $headers = array_merge(['Content-Type' => 'application/xml; charset=utf-8'], $headers);

        return new self($xml, $status, $headers);
    }

    public static function redirect(string $location, int $status = 302): self
    {
        return new self('', $status, ['Location' => $location]);
    }

    public function send(): void
    {
        if ($this->view) {
            $this->renderView();
        }

        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $this->body;
    }

    private function renderView(): void
    {
        $viewPath = __DIR__ . '/../../templates/' . $this->view . '.php';
        
        if (!file_exists($viewPath)) {
            throw new Exception("View file not found: $viewPath");
        }

        extract($this->data);

        ob_start();
        require $viewPath;
        $this->body = ob_get_clean();
    }
}
