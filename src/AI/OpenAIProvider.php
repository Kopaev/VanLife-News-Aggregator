<?php

declare(strict_types=1);

namespace App\AI;

use App\Core\Config;
use App\Service\LoggerService;
use RuntimeException;

class OpenAIProvider implements AIProviderInterface
{
    private const API_BASE = 'https://api.openai.com/v1';

    private string $apiKey;
    private string $model;
    private int $maxTokens;
    private float $temperature;
    private int $requestsPerMinute;
    private float $lastRequestTime = 0.0;

    public function __construct(private readonly Config $config, private readonly LoggerService $logger)
    {
        $openAiConfig = $this->config->get('openai', []);

        $this->apiKey = (string)($openAiConfig['api_key'] ?? '');
        $this->model = (string)($openAiConfig['model'] ?? 'gpt-4o-mini');
        $this->maxTokens = max(1, (int)($openAiConfig['max_tokens'] ?? 1000));
        $this->temperature = (float)($openAiConfig['temperature'] ?? 0.3);
        $this->requestsPerMinute = max(0, (int)($openAiConfig['requests_per_minute'] ?? 20));

        if ($this->apiKey === '') {
            throw new RuntimeException('OpenAI API key is not configured. Set OPENAI_API_KEY in .env.');
        }
    }

    public function chat(array $messages, array $options = []): AIResponse
    {
        $payload = [
            'model' => (string)($options['model'] ?? $this->model),
            'messages' => $this->normalizeMessages($messages),
            'max_tokens' => (int)($options['max_tokens'] ?? $this->maxTokens),
            'temperature' => (float)($options['temperature'] ?? $this->temperature),
        ];

        if (isset($options['response_format'])) {
            $payload['response_format'] = $options['response_format'];
        }

        if (isset($options['user'])) {
            $payload['user'] = (string)$options['user'];
        }

        $this->throttle();

        $responseData = $this->request('/chat/completions', $payload);

        if (!isset($responseData['choices'][0]['message']['content'])) {
            $this->logger->error('OpenAIProvider', 'Invalid response structure', [
                'response' => $responseData,
            ]);
            throw new RuntimeException('Invalid OpenAI response: missing content.');
        }

        $choice = $responseData['choices'][0];
        $content = (string)$choice['message']['content'];
        $finishReason = $choice['finish_reason'] ?? null;
        $model = $responseData['model'] ?? $payload['model'];
        $usage = $responseData['usage'] ?? [];

        return new AIResponse($content, $usage, $model, $finishReason, $responseData);
    }

    /**
     * @param array<int, array{role: string, content: string}> $messages
     * @return array<int, array{role: string, content: string}>
     */
    private function normalizeMessages(array $messages): array
    {
        $normalized = [];

        foreach ($messages as $message) {
            if (!is_array($message) || !isset($message['role'], $message['content'])) {
                throw new RuntimeException('Each message must be an array with "role" and "content" keys.');
            }

            $normalized[] = [
                'role' => (string)$message['role'],
                'content' => (string)$message['content'],
            ];
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function request(string $endpoint, array $payload): array
    {
        $url = self::API_BASE . $endpoint;
        $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

        if ($encodedPayload === false) {
            throw new RuntimeException('Failed to encode payload for OpenAI request.');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $encodedPayload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            $this->logger->error('OpenAIProvider', 'cURL failed', [
                'error' => $curlError,
            ]);
            throw new RuntimeException('Failed to contact OpenAI API: ' . $curlError);
        }

        $decoded = json_decode($response, true);

        if (!is_array($decoded)) {
            $this->logger->error('OpenAIProvider', 'Failed to decode response', [
                'response' => $response,
                'http_code' => $httpCode,
            ]);
            throw new RuntimeException('Failed to decode OpenAI response.');
        }

        if ($httpCode >= 400) {
            $errorMessage = $decoded['error']['message'] ?? 'Unknown OpenAI error';
            $this->logger->error('OpenAIProvider', 'OpenAI API returned error', [
                'http_code' => $httpCode,
                'error' => $errorMessage,
            ]);
            throw new RuntimeException('OpenAI API error: ' . $errorMessage);
        }

        $this->logger->debug('OpenAIProvider', 'Request completed', [
            'http_code' => $httpCode,
            'usage' => $decoded['usage'] ?? null,
        ]);

        return $decoded;
    }

    private function throttle(): void
    {
        if ($this->requestsPerMinute <= 0) {
            return;
        }

        $minInterval = 60 / $this->requestsPerMinute;
        $elapsed = microtime(true) - $this->lastRequestTime;

        if ($this->lastRequestTime > 0 && $elapsed < $minInterval) {
            $sleepMicroseconds = (int)(($minInterval - $elapsed) * 1_000_000);
            usleep($sleepMicroseconds);
        }

        $this->lastRequestTime = microtime(true);
    }
}
