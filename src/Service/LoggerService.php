<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Config;

class LoggerService
{
    private const LEVELS = [
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3,
    ];

    private string $logPath;
    private int $threshold;

    public function __construct(Config $config)
    {
        $loggingConfig = $config->get('logging', []);
        $this->logPath = rtrim($loggingConfig['path'] ?? dirname(__DIR__, 2) . '/logs', '/');
        $level = strtolower((string)($loggingConfig['level'] ?? 'info'));
        $this->threshold = self::LEVELS[$level] ?? self::LEVELS['info'];

        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0775, true);
        }
    }

    public function debug(string $channel, string $message, array $context = []): void
    {
        $this->log('debug', $channel, $message, $context);
    }

    public function info(string $channel, string $message, array $context = []): void
    {
        $this->log('info', $channel, $message, $context);
    }

    public function warning(string $channel, string $message, array $context = []): void
    {
        $this->log('warning', $channel, $message, $context);
    }

    public function error(string $channel, string $message, array $context = []): void
    {
        $this->log('error', $channel, $message, $context);
    }

    private function log(string $level, string $channel, string $message, array $context): void
    {
        $levelKey = self::LEVELS[$level] ?? self::LEVELS['info'];
        if ($levelKey < $this->threshold) {
            return;
        }

        $timestamp = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        $payload = [
            'time' => $timestamp,
            'level' => strtoupper($level),
            'channel' => $channel,
            'message' => $message,
        ];

        if (!empty($context)) {
            $payload['context'] = $context;
        }

        $line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        $file = sprintf('%s/app.log', $this->logPath);

        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}
